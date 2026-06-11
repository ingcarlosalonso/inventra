<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_presentation_id' => null,
            'description' => fake()->words(3, true),
            'quantity' => fake()->randomFloat(3, 1, 100),
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => 0,
        ];
    }
}
