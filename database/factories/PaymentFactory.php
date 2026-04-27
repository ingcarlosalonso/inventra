<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payable_type' => 'sale',
            'payable_id' => Sale::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'currency_id' => null,
            'daily_cash_id' => null,
            'amount' => fake()->randomFloat(2, 1, 10000),
            'exchange_rate' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
