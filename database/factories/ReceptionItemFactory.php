<?php

namespace Database\Factories;

use App\Models\ProductPresentation;
use App\Models\Reception;
use App\Models\ReceptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceptionItem>
 */
class ReceptionItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 100);
        $unitCost = fake()->randomFloat(2, 1, 5000);

        return [
            'reception_id' => Reception::factory(),
            'product_presentation_id' => ProductPresentation::factory(),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total' => round($quantity * $unitCost, 2),
        ];
    }
}
