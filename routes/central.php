<?php

use App\Http\Controllers\Central\AuthController;
use App\Http\Controllers\Central\MercadoPagoOAuthCallbackController;
use App\Http\Controllers\Central\MercadoPagoWebhookController;
use App\Http\Controllers\Central\ReleaseController;
use App\Http\Controllers\Central\TenantController;
use App\Http\Controllers\Central\TenantModuleController;
use Illuminate\Support\Facades\Route;

Route::domain(config('app.central_domain'))->name('central.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    // Mercado Pago notifies this single URL (configured once in the developer panel)
    // for events across every tenant's connected account; the tenant is resolved
    // from the webhook body's user_id, not from the domain.
    Route::post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle'])->name('webhooks.mercadopago');

    // Mercado Pago OAuth apps only accept one static redirect_uri, so it can't vary per
    // tenant subdomain — this single central callback resolves the tenant from the
    // cached `state` (set when the tenant started the "Conectar" flow) and redirects
    // back to that tenant's own settings page once the token exchange succeeds.
    Route::get('/oauth/mercadopago/callback', [MercadoPagoOAuthCallbackController::class, 'handle'])->name('oauth.mercadopago.callback');

    Route::middleware('auth:central')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', fn () => redirect()->route('central.tenants.index'));

        // Tenants
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('/tenants/{tenant}/modules/{module}/toggle', [TenantModuleController::class, 'toggle'])->name('tenants.modules.toggle');

        // Releases
        Route::prefix('releases')->name('releases.')->group(function () {
            Route::get('/', [ReleaseController::class, 'index'])->name('index');
            Route::post('/', [ReleaseController::class, 'store'])->name('store');
            Route::put('/{release}', [ReleaseController::class, 'update'])->name('update');
            Route::post('/{release}/publish', [ReleaseController::class, 'publish'])->name('publish');
            Route::post('/{release}/unpublish', [ReleaseController::class, 'unpublish'])->name('unpublish');
        });
    });
});
