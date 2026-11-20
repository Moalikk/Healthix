<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Notifier;
use App\Services\QueueService;

class StaffAppointmentController extends Controller
{
    public function index(Request $request)
    {
        QueueService::skipLate();

        $appointments = DB::table('appointments')
            ->join('users', 'appointments.user_id', '=', 'users.id')
            ->join('departments', 'appointments.department_id', '=', 'departments.id')
            ->when($request->query('date'), fn ($q, $v) => $q->where('appointments.appointment_date', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('appointments.status', $v))
            ->when($request->query('department_id'), fn ($q, $v) => $q->where('appointments.department_id', $v))
            ->orderByRaw("appointments.priority = 'urgent' DESC")
            ->orderBy('appointments.queue_number')
            ->orderBy('appointments.created_at')
            ->select(
                'appointments.id',
                'appointments.appointment_date',
                'appointments.complaint',
                'appointments.priority',
                'appointments.status',
                'appointments.queue_number',
                'appointments.doctor_id',
                'appointments.staff_note',
                'users.name as patient_name',
                'departments.name as department_name'
            )
            ->get();

        return response()->json(['data' => $appointments]);
    }

    // Staff menyetujui: pilih dokter, atur prioritas, beri nomor antrean
    public function confirm(Request $request, $id)
    {
        $data = $request->validate([
            'doctor_id'  => 'required|exists:doctors,id',
            'priority'   => 'nullable|in:routine,urgent',
            'staff_note' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($id, $data) {
            $appt = DB::table('appointments')->where('id', $id)->lockForUpdate()->first();

            if (!$appt) {
                return response()->json(['message' => 'Appointment not found'], 404);
            }
            if ($appt->status !== 'pending') {
                return response()->json(['message' => 'Only pending requests can be confirmed'], 422);
            }

            $sameDepartment = DB::table('doctors')
                ->where('id', $data['doctor_id'])
                ->where('department_id', $appt->department_id)
                ->exists();
            if (!$sameDepartment) {
                return response()->json(['message' => 'Doctor is not in this department'], 422);
            }

            // Nomor antrean berikutnya untuk departemen + tanggal yang sama
            $last = DB::table('appointments')
                ->where('appointment_date', $appt->appointment_date)
                ->where('department_id', $appt->department_id)
                ->whereNotNull('queue_number')
                ->lockForUpdate()
                ->max('queue_number');

            DB::table('appointments')->where('id', $id)->update([
                'doctor_id'    => $data['doctor_id'],
                'priority'     => $data['priority'] ?? $appt->priority,
                'staff_note'   => $data['staff_note'] ?? null,
                'queue_number' => ($last ?? 0) + 1,
                'status'       => 'confirmed',
                'updated_at'   => now(),
            ]);

            $updated = DB::table('appointments')->where('id', $id)->first();
            $doctor = DB::table('doctors')->where('id', $data['doctor_id'])->value('name');

            Notifier::send(
                $updated->user_id,
                $updated->id,
                'Request confirmed',
                Notifier::label($updated) . ' · Queue #' . $updated->queue_number . ' · ' . $doctor
            );

            return response()->json(['data' => $updated]);
        });
    }

    public function decline(Request $request, $id)
    {
        $data = $request->validate(['staff_note' => 'required|string|max:500']);
        return $this->changeStatus($id, 'pending', 'declined', ['staff_note' => $data['staff_note']], function ($row) {
            Notifier::send($row->user_id, $row->id, 'Request declined', Notifier::label($row) . '. Reason: ' . $row->staff_note);
        });
    }

    public function call($id)
    {
        return $this->changeStatus($id, 'confirmed', 'called', ['called_at' => now()], function ($row) {
            Notifier::send(
                $row->user_id,
                $row->id,
                "It's your turn",
                Notifier::label($row) . ' · Queue #' . $row->queue_number . '. Please come within ' . QueueService::LATE_MINUTES . ' minutes.'
            );
        });
    }

    public function complete($id)
    {
        return $this->changeStatus($id, 'called', 'completed', [], function ($row) {
            Notifier::send($row->user_id, $row->id, 'Visit completed', Notifier::label($row) . '. Thank you for visiting.');
        });
    }

    // Mengubah status hanya jika status saat ini sesuai
    private function changeStatus($id, $from, $to, array $extra = [], ?callable $after = null)
    {
        $updated = DB::table('appointments')
            ->where('id', $id)
            ->where('status', $from)
            ->update(array_merge($extra, ['status' => $to, 'updated_at' => now()]));

        if (!$updated) {
            return response()->json(['message' => "Appointment must be '$from' to become '$to'"], 422);
        }

        $row = DB::table('appointments')->where('id', $id)->first();

        if ($after) {
            $after($row);
        }

        return response()->json(['data' => $row]);
    }
}