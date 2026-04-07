<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Central routes (no tenant)
Route::middleware('api')->group(function () {
    //
});

// Tenant routes
Route::middleware(['api', 'tenant'])->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
    });
});
