<?php

namespace Database\Factories;

use App\Models\CompositeProduct;
use App\Models\CompositeProductItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompositeProductItem>
 */
class CompositeProductItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'composite_product_id' => CompositeProduct::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
