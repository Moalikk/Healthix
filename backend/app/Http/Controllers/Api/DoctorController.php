<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $doctors = DB::table('doctors')
            ->join('departments', 'doctors.department_id', '=', 'departments.id')
            ->where('doctors.is_active', true)
            ->when($request->query('department_id'), fn ($q, $id) => $q->where('doctors.department_id', $id))
            ->select(
                'doctors.id',
                'doctors.name',
                'doctors.specialization',
                'doctors.photo_url',
                'departments.name as department_name'
            )
            ->get();

        return response()->json(['data' => $doctors]);
    }
}