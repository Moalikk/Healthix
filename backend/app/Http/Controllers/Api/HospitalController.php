<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HospitalController extends Controller
{
    public function index(Request $request)
    {
        $hospitals = DB::table('hospitals')
            ->where('is_active', true)
            ->when($request->query('city'), fn ($q, $city) => $q->where('city', 'like', "%{$city}%"))
            ->select('id', 'name', 'type', 'address', 'city', 'image_url')
            ->get();

        return response()->json(['data' => $hospitals]);
    }
}
