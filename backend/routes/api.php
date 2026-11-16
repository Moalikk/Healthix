<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\HospitalController;
use App\Http\Controllers\Api\DoctorController;

Route::get('/doctors', [DoctorController::class, 'index']);

Route::get('/hospitals', [HospitalController::class, 'index']);

Route::get('/departments', [DepartmentController::class, 'index']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
