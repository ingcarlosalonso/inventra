<?php

namespace Database\Factories;

use App\Models\ProductMovementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductMovementType>
 */
class ProductMovementTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->unique()->word(),
            'is_income' => true,
            'is_active' => true,
        ];
    }

    public function outgoing(): static
    {
        return $this->state(['is_income' => false]);
    }
}
