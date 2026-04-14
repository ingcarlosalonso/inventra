<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Currency;
use App\Models\PointOfSale;
use App\Models\Sale;
use App\Models\SaleState;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => null,
            'point_of_sale_id' => PointOfSale::factory(),
            'sale_state_id' => SaleState::factory(),
            'daily_cash_id' => null,
            'currency_id' => null,
            'user_id' => User::factory(),
            'subtotal' => 0,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function withClient(): static
    {
        return $this->state(['client_id' => Client::factory()]);
    }

    public function withCurrency(): static
    {
        return $this->state(['currency_id' => Currency::factory()]);
    }
}
