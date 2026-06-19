<?php

namespace Database\Factories;

use App\Models\Barcode;
use App\Models\ProductPresentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barcode>
 */
class BarcodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_presentation_id' => ProductPresentation::factory(),
            'barcode' => fake()->unique()->ean13(),
        ];
    }
}
