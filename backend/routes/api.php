<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\HospitalController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\StaffAppointmentController;
use Illuminate\Support\Facades\DB;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'staff'])->prefix('staff')->group(function () {
    Route::get('/appointments', [StaffAppointmentController::class, 'index']);
    Route::patch('/appointments/{id}/confirm', [StaffAppointmentController::class, 'confirm']);
    Route::patch('/appointments/{id}/decline', [StaffAppointmentController::class, 'decline']);
    Route::patch('/appointments/{id}/call', [StaffAppointmentController::class, 'call']);
    Route::patch('/appointments/{id}/complete', [StaffAppointmentController::class, 'complete']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/doctors', [DoctorController::class, 'index']);

Route::get('/health', function () {
    try {
        DB::select('select 1');
        $database = 'ok';
    } catch (\Throwable $e) {
        $database = 'down';
    }

    return response()->json([
        'status'   => $database === 'ok' ? 'ok' : 'degraded',
        'database' => $database,
        'time'     => now()->toIso8601String(),
    ], $database === 'ok' ? 200 : 503);
});

Route::get('/hospitals', [HospitalController::class, 'index']);

Route::get('/departments', [DepartmentController::class, 'index']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
