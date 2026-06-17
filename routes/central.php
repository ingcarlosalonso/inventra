<?php

use App\Http\Controllers\Central\AuthController;
use App\Http\Controllers\Central\ReleaseController;
use App\Http\Controllers\Central\TenantController;
use Illuminate\Support\Facades\Route;

Route::domain(config('app.central_domain'))->name('central.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::middleware('auth:central')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', fn () => redirect()->route('central.tenants.index'));

        // Tenants
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');

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
