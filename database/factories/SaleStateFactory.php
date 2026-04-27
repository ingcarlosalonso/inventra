<?php

namespace Database\Factories;

use App\Models\SaleState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleState>
 */
class SaleStateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'           => fake()->unique()->word(),
            'color'          => fake()->hexColor(),
            'is_default'     => false,
            'is_final_state' => false,
            'is_active'      => true,
            'sort_order'     => fake()->numberBetween(0, 100),
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function finalState(): static
    {
        return $this->state(['is_final_state' => true]);
    }
}
