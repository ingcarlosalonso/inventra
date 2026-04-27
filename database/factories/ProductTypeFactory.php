<?php

namespace Database\Factories;

use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductType>
 */
class ProductTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->unique()->word(),
            'parent_id' => null,
            'is_active' => true,
        ];
    }

    public function childOf(ProductType $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}
