<?php

namespace Database\Factories;

use App\Models\Customization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customization>
 */
class CustomizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'logo_path' => null,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'font_family' => 'Inter',
        ];
    }
}
