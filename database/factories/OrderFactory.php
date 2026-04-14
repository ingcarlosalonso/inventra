<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Courier;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => null,
            'courier_id' => null,
            'order_state_id' => OrderState::factory(),
            'point_of_sale_id' => null,
            'sale_id' => null,
            'currency_id' => null,
            'user_id' => User::factory(),
            'address' => fake()->optional()->address(),
            'notes' => fake()->optional()->sentence(),
            'requires_delivery' => false,
            'delivery_date' => null,
            'scheduled_at' => null,
            'subtotal' => 0,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => 0,
        ];
    }

    public function withClient(): static
    {
        return $this->state(['client_id' => Client::factory()]);
    }

    public function withCourier(): static
    {
        return $this->state(['courier_id' => Courier::factory()]);
    }

    public function withCurrency(): static
    {
        return $this->state(['currency_id' => Currency::factory()]);
    }

    public function withSale(): static
    {
        return $this->state(fn () => ['sale_id' => Sale::factory()]);
    }

    public function requiresDelivery(): static
    {
        return $this->state([
            'requires_delivery' => true,
            'delivery_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        ]);
    }
}
