<?php

namespace Database\Factories;

use App\Models\CashMovementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovementType>
 */
class CashMovementTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->unique()->word(),
            'is_income' => true,
            'is_active' => true,
        ];
    }

    public function expense(): static
    {
        return $this->state(['is_income' => false]);
    }
}
