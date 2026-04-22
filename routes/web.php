<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\LocaleController;
use App\Models\Client;
use App\Models\Currency;
use App\Models\ProductType;
use App\Models\Supplier;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->post('/locale', [LocaleController::class, 'update'])->name('locale.update');

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
        Route::get('/products/composite', fn () => Inertia::render('Products/CompositeProducts/Index'))->name('composite-products');
        Route::get('/products/promotions', fn () => Inertia::render('Products/Promotions/Index'))->name('promotions');
        Route::get('/receptions', fn () => Inertia::render('Receptions/Index'))->name('receptions');
        Route::get('/receptions/create', fn () => Inertia::render('Receptions/Create'))->name('receptions.create');
        Route::get('/receptions/{uuid}', fn () => Inertia::render('Receptions/Show', ['uuid' => request()->route('uuid')]))->name('receptions.show');

        // Payments
        Route::get('/payments/create', fn () => Inertia::render('Payments/Create'))->name('payments.create');

        // Sales
        Route::get('/sales', fn () => Inertia::render('Sales/Index'))->name('sales');
        Route::get('/sales/create', fn () => Inertia::render('Sales/Create'))->name('sales.create');
        Route::get('/sales/{uuid}', fn () => Inertia::render('Sales/Show', ['uuid' => request()->route('uuid')]))->name('sales.show');

        // Quotes
        Route::get('/quotes', fn () => Inertia::render('Quotes/Index'))->name('quotes');
        Route::get('/quotes/create', fn () => Inertia::render('Quotes/Create'))->name('quotes.create');
        Route::get('/quotes/{uuid}', fn () => Inertia::render('Quotes/Show', ['uuid' => request()->route('uuid')]))->name('quotes.show');

        // Orders
        Route::get('/orders', fn () => Inertia::render('Orders/Index'))->name('orders');
        Route::get('/orders/create', fn () => Inertia::render('Orders/Create'))->name('orders.create');
        Route::get('/orders/{uuid}', fn () => Inertia::render('Orders/Show', ['uuid' => request()->route('uuid')]))->name('orders.show');

        // Daily Cashes
        Route::get('/daily-cashes', fn () => Inertia::render('DailyCashes/Index'))->name('daily-cashes');
        Route::get('/daily-cashes/{uuid}', fn () => Inertia::render('DailyCashes/Show', ['uuid' => request()->route('uuid')]))->name('daily-cashes.show');

        // Settings / orders config
        Route::get('/settings/order-states', fn () => Inertia::render('Settings/OrderStates/Index'))->name('settings.order-states');
        Route::get('/settings/couriers', fn () => Inertia::render('Settings/Couriers/Index'))->name('settings.couriers');

        // Users & Roles
        Route::get('/settings/users', fn () => Inertia::render('Settings/Users/Index'))->name('settings.users');
        Route::get('/settings/roles', fn () => Inertia::render('Settings/Roles/Index'))->name('settings.roles');

        // Product Movements
        Route::get('/products/movements', fn () => Inertia::render('Products/Movements/Index'))->name('product-movements');

        // Product Import
        Route::get('/products/import', fn () => Inertia::render('Products/Import/Index'))->name('products.import');

        // Bulk Price
        Route::get('/products/bulk-price', fn () => Inertia::render('Products/BulkPrice/Index'))->name('products.bulk-price');

        // Help
        Route::get('/help', fn () => app(HelpController::class)->show('dashboard'))->name('help');
        Route::get('/help/{topic}', [HelpController::class, 'show'])->name('help.topic');

        // Reports
        Route::get('/reports', fn () => Inertia::render('Reports/Index'))->name('reports');
        Route::get('/reports/sales', fn () => Inertia::render('Reports/Sales'))->name('reports.sales');
        Route::get('/reports/products', fn () => Inertia::render('Reports/Products'))->name('reports.products');
        Route::get('/reports/payments', fn () => Inertia::render('Reports/Payments'))->name('reports.payments');
        Route::get('/reports/inventory', fn () => Inertia::render('Reports/Inventory'))->name('reports.inventory');
        Route::get('/reports/daily-cashes', fn () => Inertia::render('Reports/DailyCashes'))->name('reports.daily-cashes');
        Route::get('/reports/orders', fn () => Inertia::render('Reports/Orders'))->name('reports.orders');
        Route::get('/reports/clients', fn () => Inertia::render('Reports/Clients'))->name('reports.clients');
        Route::get('/reports/purchases', fn () => Inertia::render('Reports/Purchases'))->name('reports.purchases');
    }); // end auth:sanctum
});
