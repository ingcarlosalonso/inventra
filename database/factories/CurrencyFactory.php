<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'       => fake()->unique()->currencyCode(),
            'symbol'     => fake()->currencyCode(),
            'iso_code'   => fake()->unique()->lexify('???'),
            'is_default' => false,
            'is_active'  => true,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
