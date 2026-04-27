<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class ProductResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $product = Product::factory()->create();
        $resource = ProductResource::make($product)->toArray(new Request);

        $this->assertArrayHasKey('id', $resource);
        $this->assertArrayHasKey('name', $resource);
        $this->assertArrayHasKey('description', $resource);
        $this->assertArrayHasKey('is_active', $resource);
        $this->assertArrayHasKey('created_at', $resource);
        $this->assertArrayHasKey('updated_at', $resource);
    }

    public function test_id_is_uuid_not_integer(): void
    {
        $product = Product::factory()->create();
        $resource = ProductResource::make($product)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }

    public function test_product_type_included_when_loaded(): void
    {
        $product = Product::factory()->create();
        $product->load('productType');
        $resource = ProductResource::make($product)->toArray(new Request);

        $this->assertArrayHasKey('product_type', $resource);
        $this->assertArrayHasKey('id', $resource['product_type']);
        $this->assertArrayHasKey('name', $resource['product_type']);
    }

    public function test_currency_is_null_when_not_set(): void
    {
        $product = Product::factory()->create(['currency_id' => null]);
        $product->load('currency');
        $resource = ProductResource::make($product)->toArray(new Request);

        $this->assertNull($resource['currency']);
    }
}
