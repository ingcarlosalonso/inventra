<?php

namespace Database\Factories;

use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductPresentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPresentation>
 */
class ProductPresentationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'presentation_id' => Presentation::factory(),
            'price' => fake()->randomFloat(2, 1, 10000),
            'stock' => fake()->randomFloat(3, 0, 1000),
            'min_stock' => fake()->randomFloat(3, 0, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
