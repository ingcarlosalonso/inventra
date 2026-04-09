<?php

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

    // Main modules
    Route::get('/suppliers', fn () => Inertia::render('Suppliers/Index'))->name('suppliers');
    Route::get('/clients', fn () => Inertia::render('Clients/Index'))->name('clients');
});
