<?php

namespace App\Models;

use Database\Factories\CustomizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customization extends Model
{
    /** @use HasFactory<CustomizationFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    protected $guarded = [];

    protected $attributes = [
        'primary_color' => '#4F46E5',  // indigo-600 — botones, nav activa
        'secondary_color' => '#111827', // gray-900  — fondo sidebar
        'accent_color' => '#ffffff',    // white     — fondo topbar
        'font_family' => 'Inter',
    ];
}
