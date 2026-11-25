<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\HospitalController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StaffAppointmentController;
use App\Http\Controllers\Api\SuggestionController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public (tanpa login)
|--------------------------------------------------------------------------
*/
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

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::get('/hospitals', [HospitalController::class, 'index']);
Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/doctors', [DoctorController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Citizen & staff (perlu login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('throttle:booking');

    Route::post('/suggest-department', [SuggestionController::class, 'suggest']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'read']);
});

/*
|--------------------------------------------------------------------------
| Staff saja (login + role staff)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'staff'])->prefix('staff')->group(function () {
    Route::get('/appointments', [StaffAppointmentController::class, 'index']);
    Route::patch('/appointments/{id}/confirm', [StaffAppointmentController::class, 'confirm']);
    Route::patch('/appointments/{id}/decline', [StaffAppointmentController::class, 'decline']);
    Route::patch('/appointments/{id}/call', [StaffAppointmentController::class, 'call']);
    Route::patch('/appointments/{id}/complete', [StaffAppointmentController::class, 'complete']);
});