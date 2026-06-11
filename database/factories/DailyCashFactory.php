<?php

namespace Database\Factories;

use App\Models\DailyCash;
use App\Models\PointOfSale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyCash>
 */
class DailyCashFactory extends Factory
{
    public function definition(): array
    {
        return [
            'point_of_sale_id' => PointOfSale::factory(),
            'user_id' => User::factory(),
            'opening_balance' => fake()->randomFloat(2, 0, 5000),
            'closing_balance' => null,
            'opened_at' => now(),
            'closed_at' => null,
            'is_closed' => false,
            'notes' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(function (): array {
            $closingBalance = fake()->randomFloat(2, 0, 10000);

            return [
                'closing_balance' => $closingBalance,
                'closed_at' => now(),
                'is_closed' => true,
            ];
        });
    }
}
