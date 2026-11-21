<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    // Daftar dokter aktif. Jika ?date=YYYY-MM-DD diberikan, setiap dokter
    // diberi tanda is_available (false jika sedang cuti pada tanggal itu).
    public function index(Request $request)
    {
        $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $date = $request->query('date');

        $doctors = DB::table('doctors')
            ->join('departments', 'doctors.department_id', '=', 'departments.id')
            ->when($date, function ($q) use ($date) {
                $q->leftJoin('doctor_leaves', function ($join) use ($date) {
                    $join->on('doctor_leaves.doctor_id', '=', 'doctors.id')
                         ->where('doctor_leaves.leave_date', '=', $date);
                })->addSelect('doctor_leaves.id as leave_id', 'doctor_leaves.reason as leave_reason');
            })
            ->where('doctors.is_active', true)
            ->when($request->query('department_id'), fn ($q, $id) => $q->where('doctors.department_id', $id))
            ->addSelect(
                'doctors.id',
                'doctors.name',
                'doctors.specialization',
                'doctors.photo_url',
                'departments.name as department_name'
            )
            ->orderBy('doctors.id')
            ->get()
            ->map(function ($doctor) {
                $onLeave = !empty($doctor->leave_id ?? null);

                $doctor->is_available = !$onLeave;
                $doctor->leave_reason = $onLeave ? ($doctor->leave_reason ?: 'On leave') : null;
                unset($doctor->leave_id);

                return $doctor;
            });

        return response()->json(['data' => $doctors]);
    }
}