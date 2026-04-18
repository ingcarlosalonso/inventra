<?php

namespace Database\Factories;

use App\Models\PointOfSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointOfSale>
 */
class PointOfSaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number'    => fake()->unique()->numberBetween(1, 65000),
            'name'      => fake()->company(),
            'address'   => fake()->address(),
            'is_active' => true,
        ];
    }
}
