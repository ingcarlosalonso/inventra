<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionItem>
 */
class PromotionItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
