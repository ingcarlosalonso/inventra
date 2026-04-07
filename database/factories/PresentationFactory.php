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
            'name'                 => fake()->unique()->word(),
            'abbreviation'         => fake()->lexify('???'),
            'is_active'            => true,
        ];
    }
}
