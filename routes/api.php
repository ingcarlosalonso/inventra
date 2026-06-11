<?php

use Illuminate\Support\Facades\Route;

// Central routes (no tenant)
Route::middleware('api')->group(function () {
    //
});

// Versioned tenant API routes
require base_path('routes/v1.php');
// Future: require base_path('routes/v2.php');
