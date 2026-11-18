<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    // Citizen melihat permohonan miliknya sendiri
    public function index(Request $request)
    {
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

        $id = DB::table('appointments')->insertGetId([
            'user_id'          => $request->user()->id,
            'department_id'    => $data['department_id'],
            'appointment_date' => $data['appointment_date'],
            'complaint'        => $data['complaint'] ?? null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $appointment = DB::table('appointments')->where('id', $id)->first();

        return response()->json(['data' => $appointment], 201);
    }
}