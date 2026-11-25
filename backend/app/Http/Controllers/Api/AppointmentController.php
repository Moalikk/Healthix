<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\QueueService;

class AppointmentController extends Controller
{
    // Citizen melihat permohonan miliknya sendiri
    public function index(Request $request)
    {
        QueueService::skipLate();

        $appointments = DB::table('appointments')
            ->join('departments', 'appointments.department_id', '=', 'departments.id')
            ->where('appointments.user_id', $request->user()->id)
            ->orderByDesc('appointments.created_at')
            ->select(
                'appointments.id',
                'appointments.appointment_date',
                'appointments.complaint',
                'appointments.status',
                'appointments.queue_number',
                'departments.name as department_name'
            )
            ->get();

        return response()->json(['data' => $appointments]);
    }

    // Citizen mengajukan permohonan baru
    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id'    => 'required|exists:departments,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'complaint'        => 'nullable|string|max:1000',
        ]);

        $userId = $request->user()->id;

        // Kunci baris user supaya dua request bersamaan tidak lolos pengecekan ganda
        $id = DB::transaction(function () use ($data, $userId) {
            DB::table('users')->where('id', $userId)->lockForUpdate()->first();

            $duplicate = DB::table('appointments')
                ->where('user_id', $userId)
                ->where('department_id', $data['department_id'])
                ->where('appointment_date', $data['appointment_date'])
                ->whereIn('status', ['pending', 'confirmed', 'called'])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'department_id' => 'You already have an active request for this department on this date.',
                ]);
            }

            return DB::table('appointments')->insertGetId([
                'user_id'          => $userId,
                'department_id'    => $data['department_id'],
                'appointment_date' => $data['appointment_date'],
                'complaint'        => $data['complaint'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        });

        $appointment = DB::table('appointments')->where('id', $id)->first();

        return response()->json(['data' => $appointment], 201);
    }
}