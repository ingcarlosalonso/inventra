<?php

namespace Database\Factories;

use App\Models\CashMovement;
use App\Models\CashMovementType;
use App\Models\DailyCash;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'daily_cash_id' => DailyCash::factory(),
            'cash_movement_type_id' => CashMovementType::factory(),
            'user_id' => User::factory(),
            'reception_id' => null,
            'amount' => fake()->randomFloat(2, 10, 5000),
            'notes' => null,
        ];
    }
}
