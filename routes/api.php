<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\CashMovementTypeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DailyCashController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderStateController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PointOfSaleController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\PresentationTypeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMovementTypeController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleStateController;
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

        // Dashboard
        Route::get('dashboard', DashboardController::class);

        // Product Types
        Route::apiResource('product-types', ProductTypeController::class)->except(['show']);
        Route::patch('product-types/{productType}/toggle', [ProductTypeController::class, 'toggle']);

        // Product Movement Types
        Route::apiResource('product-movement-types', ProductMovementTypeController::class)->except(['show']);

        // Cash Movement Types
        Route::apiResource('cash-movement-types', CashMovementTypeController::class)->except(['show']);

        // Daily Cashes
        Route::post('daily-cashes/{dailyCash}/close', [DailyCashController::class, 'close']);
        Route::post('daily-cashes/{dailyCash}/movements', [CashMovementController::class, 'store']);
        Route::delete('daily-cashes/{dailyCash}/movements/{cashMovement}', [CashMovementController::class, 'destroy']);
        Route::apiResource('daily-cashes', DailyCashController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

        // Suppliers
        Route::apiResource('suppliers', SupplierController::class)->except(['show']);

        // Clients
        Route::apiResource('clients', ClientController::class)->except(['show']);

        // Currencies
        Route::apiResource('currencies', CurrencyController::class)->except(['show']);
        Route::patch('currencies/{currency}/toggle', [CurrencyController::class, 'toggle']);

        // Points of Sale
        Route::apiResource('points-of-sale', PointOfSaleController::class)
            ->except(['show'])
            ->parameters(['points-of-sale' => 'pointOfSale']);
        Route::patch('points-of-sale/{pointOfSale}/toggle', [PointOfSaleController::class, 'toggle']);

        // Sale States
        Route::apiResource('sale-states', SaleStateController::class)->except(['show']);
        Route::patch('sale-states/{saleState}/toggle', [SaleStateController::class, 'toggle']);

        // Payment Methods
        Route::apiResource('payment-methods', PaymentMethodController::class)->except(['show']);
        Route::patch('payment-methods/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle']);

        // Presentation Types
        Route::apiResource('presentation-types', PresentationTypeController::class)->except(['show']);
        Route::patch('presentation-types/{presentationType}/toggle', [PresentationTypeController::class, 'toggle']);

        // Presentations
        Route::apiResource('presentations', PresentationController::class)->except(['show']);
        Route::patch('presentations/{presentation}/toggle', [PresentationController::class, 'toggle']);

        // Products
        Route::apiResource('products', ProductController::class)->except(['show']);
        Route::patch('products/{product}/toggle', [ProductController::class, 'toggle']);

        // Receptions
        Route::apiResource('receptions', ReceptionController::class)->only(['index', 'store', 'show', 'destroy']);

        // Sales
        Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show', 'destroy']);

        // Quotes
        Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert']);
        Route::apiResource('quotes', QuoteController::class)->only(['index', 'store', 'show', 'destroy']);

        // Orders
        Route::patch('orders/{order}/state', [OrderController::class, 'updateState']);
        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show', 'destroy']);

        // Order States
        Route::apiResource('order-states', OrderStateController::class)->except(['show']);
        Route::patch('order-states/{orderState}/toggle', [OrderStateController::class, 'toggle']);

        // Couriers
        Route::apiResource('couriers', CourierController::class)->except(['show']);
        Route::patch('couriers/{courier}/toggle', [CourierController::class, 'toggle']);
    });
});
