<?php

namespace Database\Factories;

use App\Models\Barcode;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barcode>
 */
class BarcodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'barcode' => fake()->unique()->ean13(),
        ];
    }
}
