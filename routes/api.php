<?php

use Illuminate\Support\Facades\Route;

// Rutas centrales (sin tenant)
Route::middleware(['api'])->group(function () {
    // login central, register tenant, etc.
});

// Rutas tenant
Route::middleware(['api', 'tenant'])->group(function () {
    // todas las rutas del negocio van acá
});
