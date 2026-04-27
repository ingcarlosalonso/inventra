<?php

namespace Database\Factories;

use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 100);
        $unitPrice = fake()->randomFloat(2, 1, 10000);

        return [
            'sale_id' => Sale::factory(),
            'product_presentation_id' => ProductPresentation::factory(),
            'description' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => round($quantity * $unitPrice, 2),
        ];
    }
}
