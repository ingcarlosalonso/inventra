<?php

namespace Database\Factories;

use App\Models\Reception;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reception>
 */
class ReceptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'daily_cash_id' => null,
            'user_id' => User::factory(),
            'supplier_invoice' => fake()->optional()->numerify('FAC-####'),
            'total' => 0,
            'notes' => fake()->optional()->sentence(),
            'received_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
