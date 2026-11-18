<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffAppointmentController extends Controller
{
    public function index(Request $request)
    {
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
}