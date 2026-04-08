<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('tenant')->group(function () {
    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    // Settings / parametrization
    Route::get('/settings/product-types', fn () => Inertia::render('Settings/ProductTypes/Index'))->name('settings.product-types');
    Route::get('/settings/product-movement-types', fn () => Inertia::render('Settings/ProductMovementTypes/Index'))->name('settings.product-movement-types');
    Route::get('/settings/cash-movement-types', fn () => Inertia::render('Settings/CashMovementTypes/Index'))->name('settings.cash-movement-types');

    // Main modules
    Route::get('/suppliers', fn () => Inertia::render('Suppliers/Index'))->name('suppliers');
    Route::get('/clients', fn () => Inertia::render('Clients/Index'))->name('clients');
});
