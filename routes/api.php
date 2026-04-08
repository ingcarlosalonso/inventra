<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashMovementTypeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductMovementTypeController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\SupplierController;
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

        // Product Types
        Route::apiResource('product-types', ProductTypeController::class)->except(['show']);
        Route::patch('product-types/{productType}/toggle', [ProductTypeController::class, 'toggle']);

        // Product Movement Types
        Route::apiResource('product-movement-types', ProductMovementTypeController::class)->except(['show']);

        // Cash Movement Types
        Route::apiResource('cash-movement-types', CashMovementTypeController::class)->except(['show']);

        // Suppliers
        Route::apiResource('suppliers', SupplierController::class)->except(['show']);

        // Clients
        Route::apiResource('clients', ClientController::class)->except(['show']);
    });
});
