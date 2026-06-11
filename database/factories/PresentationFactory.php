<?php

namespace Database\Factories;

use App\Models\Presentation;
use App\Models\PresentationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presentation>
 */
class PresentationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'presentation_type_id' => PresentationType::factory(),
            'quantity' => fake()->randomElement([1, 100, 250, 500, 1000]),
            'is_active' => true,
        ];
    }
}
