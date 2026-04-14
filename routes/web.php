<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocaleController;
use App\Models\Client;
use App\Models\Currency;
use App\Models\ProductType;
use App\Models\Supplier;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Multitenancy\Contracts\IsTenant;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

// TEMP DEBUG - remove after diagnosis
Route::get('/debug-tenant', function () {
    $tenant = app(IsTenant::class)::current();

    return response()->json([
        'host' => request()->getHost(),
        'tenant' => $tenant ? $tenant->toArray() : null,
    ]);
});

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
        Route::get('/settings/presentation-types', fn () => Inertia::render('Settings/PresentationTypes/Index'))->name('settings.presentation-types');
        Route::get('/settings/presentations', fn () => Inertia::render('Settings/Presentations/Index'))->name('settings.presentations');
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
        Route::get('/products', fn () => Inertia::render('Products/Index'))->name('products');
        Route::get('/receptions', fn () => Inertia::render('Receptions/Index'))->name('receptions');
        Route::get('/receptions/create', fn () => Inertia::render('Receptions/Create'))->name('receptions.create');
        Route::get('/receptions/{uuid}', fn () => Inertia::render('Receptions/Show', ['uuid' => request()->route('uuid')]))->name('receptions.show');

        // Sales
        Route::get('/sales', fn () => Inertia::render('Sales/Index'))->name('sales');
        Route::get('/sales/create', fn () => Inertia::render('Sales/Create'))->name('sales.create');
        Route::get('/sales/{uuid}', fn () => Inertia::render('Sales/Show', ['uuid' => request()->route('uuid')]))->name('sales.show');
    }); // end auth:sanctum
});
