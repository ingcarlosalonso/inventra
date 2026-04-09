<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocaleController;
use App\Models\Client;
use App\Models\Currency;
use App\Models\ProductType;
use App\Models\Supplier;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('tenant')->group(function () {
    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    Route::middleware('auth')->group(function () {
        Route::get('/', fn () => redirect()->route('dashboard'));
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard', [
                'counts' => [
                    'suppliers' => Supplier::count(),
                    'clients' => Client::count(),
                    'product_types' => ProductType::count(),
                    'currencies' => Currency::count(),
                ],
            ]);
        })->name('dashboard');

        // Settings / parametrization
        Route::get('/settings/product-types', fn () => Inertia::render('Settings/ProductTypes/Index'))->name('settings.product-types');
        Route::get('/settings/product-movement-types', fn () => Inertia::render('Settings/ProductMovementTypes/Index'))->name('settings.product-movement-types');
        Route::get('/settings/cash-movement-types', fn () => Inertia::render('Settings/CashMovementTypes/Index'))->name('settings.cash-movement-types');

        // Settings / currencies
        Route::get('/settings/currencies', fn () => Inertia::render('Settings/Currencies/Index'))->name('settings.currencies');

        // Settings / sales config
        Route::get('/settings/points-of-sale', fn () => Inertia::render('Settings/PointsOfSale/Index'))->name('settings.points-of-sale');
        Route::get('/settings/sale-states', fn () => Inertia::render('Settings/SaleStates/Index'))->name('settings.sale-states');
        Route::get('/settings/payment-methods', fn () => Inertia::render('Settings/PaymentMethods/Index'))->name('settings.payment-methods');

        // Main modules
        Route::get('/suppliers', fn () => Inertia::render('Suppliers/Index'))->name('suppliers');
        Route::get('/clients', fn () => Inertia::render('Clients/Index'))->name('clients');
    }); // end auth:sanctum
});
