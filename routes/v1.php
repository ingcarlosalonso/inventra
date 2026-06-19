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
use App\Http\Controllers\ProductPresentationBarcodeController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReleaseReadController;
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
        Route::put('profile/password', [ProfileController::class, 'updatePassword']);

        // ── Assistant ─────────────────────────────────────────────────────────
        Route::post('assistant/chat', [AssistantController::class, 'chat'])->middleware('throttle:20,1');

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

        // ── Clients ───────────────────────────────────────────────────────────
        Route::middleware('permission:list_clients')->group(function () {
            Route::get('clients', [ClientController::class, 'index']);
        });
        Route::middleware('permission:create_edit_delete_clients')->group(function () {
            Route::post('clients', [ClientController::class, 'store']);
            Route::put('clients/{client}', [ClientController::class, 'update']);
            Route::delete('clients/{client}', [ClientController::class, 'destroy']);
        });

        // ── Suppliers ─────────────────────────────────────────────────────────
        Route::middleware('permission:list_suppliers')->group(function () {
            Route::get('suppliers', [SupplierController::class, 'index']);
        });
        Route::middleware('permission:create_edit_delete_suppliers')->group(function () {
            Route::post('suppliers', [SupplierController::class, 'store']);
            Route::put('suppliers/{supplier}', [SupplierController::class, 'update']);
            Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy']);
        });

        // ── Products ──────────────────────────────────────────────────────────
        Route::get('products/barcode/{barcode}', [ProductPresentationBarcodeController::class, 'show'])
            ->middleware('permission:list_products');

        Route::middleware('permission:list_products')->group(function () {
            Route::get('products', [ProductController::class, 'index']);
        });
        Route::middleware('permission:create_edit_delete_products')->group(function () {
            Route::post('products', [ProductController::class, 'store']);
            Route::put('products/{product}', [ProductController::class, 'update']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);
            Route::patch('products/{product}/toggle', [ProductController::class, 'toggle']);
            Route::post('products/import', [ProductImportController::class, 'store']);
        });

        Route::prefix('products')->group(function () {
            // Bulk price
            Route::middleware('permission:bulk_update_product_price')->group(function () {
                Route::get('bulk-price/preview', [BulkPriceController::class, 'preview']);
                Route::post('bulk-price', [BulkPriceController::class, 'update']);
            });

            // Composite products
            Route::middleware('permission:list_products')->group(function () {
                Route::get('composite', [CompositeProductController::class, 'index']);
            });
            Route::middleware('permission:create_edit_delete_products')->group(function () {
                Route::post('composite', [CompositeProductController::class, 'store']);
                Route::put('composite/{compositeProduct}', [CompositeProductController::class, 'update']);
                Route::delete('composite/{compositeProduct}', [CompositeProductController::class, 'destroy']);
                Route::patch('composite/{compositeProduct}/toggle', [CompositeProductController::class, 'toggle']);
            });

            // Promotions
            Route::middleware('permission:list_products')->group(function () {
                Route::get('promotions', [PromotionController::class, 'index']);
            });
            Route::middleware('permission:create_edit_delete_products')->group(function () {
                Route::post('promotions', [PromotionController::class, 'store']);
                Route::put('promotions/{promotion}', [PromotionController::class, 'update']);
                Route::delete('promotions/{promotion}', [PromotionController::class, 'destroy']);
                Route::patch('promotions/{promotion}/toggle', [PromotionController::class, 'toggle']);
            });

            // Product movement types — GET open to all authenticated users (needed for movement form dropdown)
            Route::get('movement-types', [ProductMovementTypeController::class, 'index']);
            Route::middleware('permission:create_edit_delete_product_movement_types')->group(function () {
                Route::post('movement-types', [ProductMovementTypeController::class, 'store']);
                Route::put('movement-types/{productMovementType}', [ProductMovementTypeController::class, 'update']);
                Route::delete('movement-types/{productMovementType}', [ProductMovementTypeController::class, 'destroy']);
            });

            // Product movements (extra movements)
            Route::middleware('permission:list_products')->group(function () {
                Route::get('movements', [ProductMovementController::class, 'index']);
            });
            Route::middleware('permission:create_edit_delete_products')->group(function () {
                Route::post('movements', [ProductMovementController::class, 'store']);
                Route::delete('movements/{productMovement}', [ProductMovementController::class, 'destroy']);
            });

            // Presentation types — GET open to all authenticated users (needed for product/reception forms)
            Route::get('presentation-types', [PresentationTypeController::class, 'index']);
            Route::middleware('permission:create_edit_delete_presentation_types')->group(function () {
                Route::post('presentation-types', [PresentationTypeController::class, 'store']);
                Route::put('presentation-types/{presentationType}', [PresentationTypeController::class, 'update']);
                Route::delete('presentation-types/{presentationType}', [PresentationTypeController::class, 'destroy']);
                Route::patch('presentation-types/{presentationType}/toggle', [PresentationTypeController::class, 'toggle']);
            });

            // Presentations — GET open to all authenticated users (needed for product/reception forms)
            Route::get('presentations', [PresentationController::class, 'index']);
            Route::middleware('permission:create_edit_delete_presentations')->group(function () {
                Route::post('presentations', [PresentationController::class, 'store']);
                Route::put('presentations/{presentation}', [PresentationController::class, 'update']);
                Route::delete('presentations/{presentation}', [PresentationController::class, 'destroy']);
                Route::patch('presentations/{presentation}/toggle', [PresentationController::class, 'toggle']);
            });

            // Product types — GET open to all authenticated users (needed for product filters/forms)
            Route::get('types', [ProductTypeController::class, 'index']);
            Route::middleware('permission:create_edit_delete_product_types')->group(function () {
                Route::post('types', [ProductTypeController::class, 'store']);
                Route::put('types/{productType}', [ProductTypeController::class, 'update']);
                Route::delete('types/{productType}', [ProductTypeController::class, 'destroy']);
                Route::patch('types/{productType}/toggle', [ProductTypeController::class, 'toggle']);
            });
        });

        // ── Receptions ────────────────────────────────────────────────────────
        Route::middleware('permission:list_receptions')->group(function () {
            Route::get('receptions', [ReceptionController::class, 'index']);
            Route::get('receptions/{reception}', [ReceptionController::class, 'show']);
        });
        Route::middleware('permission:create_edit_delete_receptions')->group(function () {
            Route::post('receptions', [ReceptionController::class, 'store']);
            Route::delete('receptions/{reception}', [ReceptionController::class, 'destroy']);
        });

        // ── Quotes ────────────────────────────────────────────────────────────
        Route::middleware('permission:list_quotes')->group(function () {
            Route::get('quotes', [QuoteController::class, 'index']);
            Route::get('quotes/{quote}', [QuoteController::class, 'show']);
        });
        Route::middleware('permission:create_edit_delete_quotes')->group(function () {
            Route::post('quotes', [QuoteController::class, 'store']);
            Route::delete('quotes/{quote}', [QuoteController::class, 'destroy']);
            Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert']);
        });

        // ── Orders ────────────────────────────────────────────────────────────
        // Sub-resources before {order} wildcard to avoid route shadowing
        Route::prefix('orders')->group(function () {
            // Couriers GET open to all authenticated users (needed for order assignment dropdown)
            Route::get('couriers', [CourierController::class, 'index']);
            Route::middleware('permission:create_edit_delete_couriers')->group(function () {
                Route::post('couriers', [CourierController::class, 'store']);
                Route::put('couriers/{courier}', [CourierController::class, 'update']);
                Route::delete('couriers/{courier}', [CourierController::class, 'destroy']);
                Route::patch('couriers/{courier}/toggle', [CourierController::class, 'toggle']);
            });

            // Order states GET open to all authenticated users (needed for order form dropdowns)
            Route::get('states', [OrderStateController::class, 'index'])->name('order-states.index');
            Route::middleware('permission:create_edit_delete_order_states')->group(function () {
                Route::post('states', [OrderStateController::class, 'store'])->name('order-states.store');
                Route::put('states/{orderState}', [OrderStateController::class, 'update'])->name('order-states.update');
                Route::delete('states/{orderState}', [OrderStateController::class, 'destroy'])->name('order-states.destroy');
                Route::patch('states/{orderState}/toggle', [OrderStateController::class, 'toggle']);
            });
        });

        Route::middleware('permission:list_orders')->group(function () {
            Route::get('orders', [OrderController::class, 'index']);
            Route::get('orders/{order}', [OrderController::class, 'show']);
        });
        Route::middleware('permission:create_edit_delete_orders')->group(function () {
            Route::post('orders', [OrderController::class, 'store']);
            Route::delete('orders/{order}', [OrderController::class, 'destroy']);
            Route::patch('orders/{order}/state', [OrderController::class, 'updateState']);
        });

        // ── Daily Cash ────────────────────────────────────────────────────────
        Route::middleware('permission:list_daily_cashes')->group(function () {
            Route::get('daily-cashes', [DailyCashController::class, 'index']);
            Route::get('daily-cashes/{dailyCash}', [DailyCashController::class, 'show']);
        });
        Route::middleware('permission:enable_close_daily_cash')->group(function () {
            Route::post('daily-cashes', [DailyCashController::class, 'store']);
            Route::put('daily-cashes/{dailyCash}', [DailyCashController::class, 'update']);
            Route::delete('daily-cashes/{dailyCash}', [DailyCashController::class, 'destroy']);
            Route::post('daily-cashes/{dailyCash}/close', [DailyCashController::class, 'close']);
            Route::post('daily-cashes/{dailyCash}/movements', [CashMovementController::class, 'store']);
            Route::delete('daily-cashes/{dailyCash}/movements/{cashMovement}', [CashMovementController::class, 'destroy']);
        });

        // Cash movement types — GET open to all authenticated users (needed for cash movement form)
        Route::get('cash-movement-types', [CashMovementTypeController::class, 'index']);
        Route::middleware('permission:create_edit_delete_cash_movement_types')->group(function () {
            Route::post('cash-movement-types', [CashMovementTypeController::class, 'store']);
            Route::put('cash-movement-types/{cashMovementType}', [CashMovementTypeController::class, 'update']);
            Route::delete('cash-movement-types/{cashMovementType}', [CashMovementTypeController::class, 'destroy']);
        });

        // ── Sales ─────────────────────────────────────────────────────────────
        // Sub-resources before {sale} wildcard to avoid route shadowing
        Route::prefix('sales')->group(function () {
            Route::middleware('permission:list_sales')->group(function () {
                Route::get('payments/pending', [PaymentController::class, 'pending']);
            });

            Route::middleware('permission:create_edit_delete_sales')->group(function () {
                Route::post('payments', [PaymentController::class, 'store']);
            });

            // Payment methods GET open to all authenticated users (needed for sale creation form)
            Route::get('payment-methods', [PaymentMethodController::class, 'index']);
            Route::middleware('permission:create_edit_delete_payment_methods')->group(function () {
                Route::post('payment-methods', [PaymentMethodController::class, 'store']);
                Route::put('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update']);
                Route::delete('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy']);
                Route::patch('payment-methods/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle']);
            });

            // Points of sale GET open to all authenticated users (needed for sale/cash creation forms)
            Route::get('points-of-sale', [PointOfSaleController::class, 'index']);
            Route::middleware('permission:create_edit_delete_points_of_sale')->group(function () {
                Route::post('points-of-sale', [PointOfSaleController::class, 'store']);
                Route::put('points-of-sale/{pointOfSale}', [PointOfSaleController::class, 'update']);
                Route::delete('points-of-sale/{pointOfSale}', [PointOfSaleController::class, 'destroy']);
                Route::patch('points-of-sale/{pointOfSale}/toggle', [PointOfSaleController::class, 'toggle']);
            });

            // Sale states GET open to all authenticated users (needed for sale form dropdowns)
            Route::get('states', [SaleStateController::class, 'index'])->name('sale-states.index');
            Route::middleware('permission:create_edit_delete_sale_states')->group(function () {
                Route::post('states', [SaleStateController::class, 'store'])->name('sale-states.store');
                Route::put('states/{saleState}', [SaleStateController::class, 'update'])->name('sale-states.update');
                Route::delete('states/{saleState}', [SaleStateController::class, 'destroy'])->name('sale-states.destroy');
                Route::patch('states/{saleState}/toggle', [SaleStateController::class, 'toggle']);
            });
        });

        Route::middleware('permission:list_sales')->group(function () {
            Route::get('sales', [SaleController::class, 'index']);
            Route::get('sales/{sale}', [SaleController::class, 'show']);
        });
        Route::middleware('permission:create_edit_delete_sales')->group(function () {
            Route::post('sales', [SaleController::class, 'store']);
            Route::delete('sales/{sale}', [SaleController::class, 'destroy']);
        });

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

        // ── Settings ──────────────────────────────────────────────────────────
        Route::prefix('settings')->group(function () {
            // Currencies GET open to all authenticated users (needed for product pricing forms)
            Route::get('currencies', [CurrencyController::class, 'index']);
            Route::middleware('permission:create_edit_delete_currencies')->group(function () {
                Route::post('currencies', [CurrencyController::class, 'store']);
                Route::put('currencies/{currency}', [CurrencyController::class, 'update']);
                Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy']);
                Route::patch('currencies/{currency}/toggle', [CurrencyController::class, 'toggle']);
            });

            Route::middleware('permission:manage_customization')->group(function () {
                Route::get('customization', [CustomizationController::class, 'show']);
                Route::post('customization', [CustomizationController::class, 'update']);
            });

            Route::middleware('permission:create_edit_delete_roles')->group(function () {
                Route::get('permissions', [PermissionController::class, 'index']);
                Route::apiResource('roles', RoleController::class);
            });

            Route::middleware('permission:create_edit_delete_users')->group(function () {
                Route::apiResource('users', UserController::class)->except(['show']);
                Route::patch('users/{user}/toggle', [UserController::class, 'toggle']);
            });
        });

        // ── Releases ──────────────────────────────────────────────────────────
        Route::post('releases/{uuid}/read', [ReleaseReadController::class, 'store']);
    });
});
