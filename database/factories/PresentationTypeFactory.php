<?php

namespace Database\Factories;

use App\Models\PresentationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresentationType>
 */
class PresentationTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'abbreviation' => fake()->unique()->lexify('??'),
            'is_active' => true,
        ];
    }
}
