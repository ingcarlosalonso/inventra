<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulkPriceController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\CashMovementTypeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompositeProductController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomizationController;
use App\Http\Controllers\DailyCashController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderStateController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PointOfSaleController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\PresentationTypeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProductMovementController;
use App\Http\Controllers\ProductMovementTypeController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleStateController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant', 'tenant.active'])->prefix('v1')->group(function () {

    // ── Auth ──────────────────────────────────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // ── Assistant ─────────────────────────────────────────────────────────
        Route::post('assistant/chat', [AssistantController::class, 'chat'])->middleware('throttle:20,1');

        // ── Clients ───────────────────────────────────────────────────────────
        Route::apiResource('clients', ClientController::class)->except(['show']);

        // ── Daily Cash ────────────────────────────────────────────────────────
        Route::apiResource('cash-movement-types', CashMovementTypeController::class)->except(['show']);

        Route::post('daily-cashes/{dailyCash}/close', [DailyCashController::class, 'close']);
        Route::post('daily-cashes/{dailyCash}/movements', [CashMovementController::class, 'store']);
        Route::delete('daily-cashes/{dailyCash}/movements/{cashMovement}', [CashMovementController::class, 'destroy']);
        Route::apiResource('daily-cashes', DailyCashController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

        // ── Dashboard ─────────────────────────────────────────────────────────
        Route::get('dashboard', DashboardController::class);

        // ── Notifications ─────────────────────────────────────────────────────
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('read-all', [NotificationController::class, 'markAllAsRead']);
            Route::post('{notification}/read', [NotificationController::class, 'markAsRead']);
            Route::delete('{notification}', [NotificationController::class, 'destroy']);
        });

        // ── Orders ────────────────────────────────────────────────────────────
        Route::patch('orders/{order}/state', [OrderController::class, 'updateState']);

        Route::prefix('orders')->group(function () {
            Route::apiResource('couriers', CourierController::class)->except(['show']);
            Route::patch('couriers/{courier}/toggle', [CourierController::class, 'toggle']);

            Route::apiResource('states', OrderStateController::class)->except(['show']);
            Route::patch('states/{orderState}/toggle', [OrderStateController::class, 'toggle']);
        });

        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show', 'destroy']);

        // ── Products ──────────────────────────────────────────────────────────
        Route::apiResource('products', ProductController::class)->except(['show']);
        Route::patch('products/{product}/toggle', [ProductController::class, 'toggle']);

        Route::prefix('products')->group(function () {
            Route::middleware('permission:bulk_update_product_price')->group(function () {
                Route::get('bulk-price/preview', [BulkPriceController::class, 'preview']);
                Route::post('bulk-price', [BulkPriceController::class, 'update']);
            });

            Route::apiResource('composite', CompositeProductController::class)->except(['show']);
            Route::patch('composite/{compositeProduct}/toggle', [CompositeProductController::class, 'toggle']);

            Route::post('import', [ProductImportController::class, 'store']);

            Route::apiResource('movement-types', ProductMovementTypeController::class)->except(['show']);

            Route::apiResource('movements', ProductMovementController::class)->only(['index', 'store', 'destroy']);

            Route::apiResource('presentation-types', PresentationTypeController::class)->except(['show']);
            Route::patch('presentation-types/{presentationType}/toggle', [PresentationTypeController::class, 'toggle']);

            Route::apiResource('presentations', PresentationController::class)->except(['show']);
            Route::patch('presentations/{presentation}/toggle', [PresentationController::class, 'toggle']);

            Route::apiResource('promotions', PromotionController::class)->except(['show']);
            Route::patch('promotions/{promotion}/toggle', [PromotionController::class, 'toggle']);

            Route::apiResource('types', ProductTypeController::class)->except(['show']);
            Route::patch('types/{productType}/toggle', [ProductTypeController::class, 'toggle']);
        });

        // ── Quotes ────────────────────────────────────────────────────────────
        Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert']);
        Route::apiResource('quotes', QuoteController::class)->only(['index', 'store', 'show', 'destroy']);

        // ── Receptions ────────────────────────────────────────────────────────
        Route::apiResource('receptions', ReceptionController::class)->only(['index', 'store', 'show', 'destroy']);

        // ── Reports ───────────────────────────────────────────────────────────
        Route::prefix('reports')->middleware('throttle:30,1')->group(function () {
            Route::middleware('permission:list_report_clients')->group(function () {
                Route::get('clients', [ReportController::class, 'clients']);
                Route::get('clients/export', [ReportController::class, 'clientsExport']);
            });

            Route::middleware('permission:list_report_daily_cashes')->group(function () {
                Route::get('daily-cashes', [ReportController::class, 'dailyCashes']);
                Route::get('daily-cashes/export', [ReportController::class, 'dailyCashesExport']);
            });

            Route::middleware('permission:list_report_inventory')->group(function () {
                Route::get('inventory', [ReportController::class, 'inventory']);
                Route::get('inventory/export', [ReportController::class, 'inventoryExport']);
            });

            Route::middleware('permission:list_report_orders')->group(function () {
                Route::get('orders', [ReportController::class, 'orders']);
                Route::get('orders/export', [ReportController::class, 'ordersExport']);
            });

            Route::middleware('permission:list_report_payments')->group(function () {
                Route::get('payments', [ReportController::class, 'payments']);
                Route::get('payments/export', [ReportController::class, 'paymentsExport']);
            });

            Route::middleware('permission:list_report_products')->group(function () {
                Route::get('products', [ReportController::class, 'products']);
                Route::get('products/export', [ReportController::class, 'productsExport']);
            });

            Route::middleware('permission:list_report_purchases')->group(function () {
                Route::get('purchases', [ReportController::class, 'purchases']);
                Route::get('purchases/export', [ReportController::class, 'purchasesExport']);
            });

            Route::middleware('permission:list_report_sales')->group(function () {
                Route::get('sales', [ReportController::class, 'sales']);
                Route::get('sales/export', [ReportController::class, 'salesExport']);
            });
        });

        // ── Sales ─────────────────────────────────────────────────────────────
        Route::prefix('sales')->group(function () {
            Route::apiResource('payment-methods', PaymentMethodController::class)->except(['show']);
            Route::patch('payment-methods/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle']);

            Route::get('payments/pending', [PaymentController::class, 'pending']);
            Route::post('payments', [PaymentController::class, 'store']);

            Route::apiResource('points-of-sale', PointOfSaleController::class)
                ->except(['show'])
                ->parameters(['points-of-sale' => 'pointOfSale']);
            Route::patch('points-of-sale/{pointOfSale}/toggle', [PointOfSaleController::class, 'toggle']);

            Route::apiResource('states', SaleStateController::class)->except(['show']);
            Route::patch('states/{saleState}/toggle', [SaleStateController::class, 'toggle']);
        });

        Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show', 'destroy']);

        // ── Settings ──────────────────────────────────────────────────────────
        Route::prefix('settings')->group(function () {
            Route::apiResource('currencies', CurrencyController::class)->except(['show']);
            Route::patch('currencies/{currency}/toggle', [CurrencyController::class, 'toggle']);

            Route::get('customization', [CustomizationController::class, 'show']);
            Route::post('customization', [CustomizationController::class, 'update']);

            Route::middleware('permission:create_edit_delete_roles')->group(function () {
                Route::get('permissions', [PermissionController::class, 'index']);
                Route::apiResource('roles', RoleController::class);
            });

            Route::middleware('permission:create_edit_delete_users')->group(function () {
                Route::apiResource('users', UserController::class)->except(['show']);
                Route::patch('users/{user}/toggle', [UserController::class, 'toggle']);
            });
        });

        // ── Suppliers ─────────────────────────────────────────────────────────
        Route::apiResource('suppliers', SupplierController::class)->except(['show']);
    });
});
