<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = DB::table('departments')
            ->join('hospitals', 'departments.hospital_id', '=', 'hospitals.id')
            ->select(
                'departments.id',
                'departments.name',
                'departments.description',
                'departments.common_conditions',
                'hospitals.name as hospital_name'
            )
            ->get();

        return response()->json(['data' => $departments]);
    }
}
