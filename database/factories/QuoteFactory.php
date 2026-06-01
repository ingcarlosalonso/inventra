<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    public function definition(): array
    {
        $starts = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'client_id' => null,
            'user_id' => User::factory(),
            'currency_id' => null,
            'sale_id' => null,
            'subtotal' => 0,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'notes' => fake()->optional()->sentence(),
            'starts_at' => $starts->format('Y-m-d'),
            'expires_at' => fake()->optional()->dateTimeBetween($starts, '+1 month')?->format('Y-m-d'),
        ];
    }

    public function withClient(): static
    {
        return $this->state(['client_id' => Client::factory()]);
    }

    public function converted(): static
    {
        return $this->state(fn () => ['sale_id' => Sale::factory()]);
    }
}
