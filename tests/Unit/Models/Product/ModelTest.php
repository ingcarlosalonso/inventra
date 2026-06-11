<?php

namespace Tests\Unit\Models\Product;

use App\Models\Model;
use App\Models\Product;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Product::tableName(), [
            'id', 'uuid', 'product_type_id', 'currency_id',
            'name', 'description', 'cost',
            'is_active', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Product);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $product = Product::factory()->create();

        $this->assertNotNull($product->uuid);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue($product->is_active);
    }

    public function test_inactive_state(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->assertFalse($product->is_active);
    }
}
