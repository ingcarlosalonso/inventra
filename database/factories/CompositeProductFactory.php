<?php

namespace Database\Factories;

use App\Models\CompositeProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompositeProduct>
 */
class CompositeProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => fake()->optional()->bothify('KIT-###'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
