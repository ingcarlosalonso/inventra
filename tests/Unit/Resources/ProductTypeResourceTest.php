<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\ProductType\ProductTypeResource;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class ProductTypeResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $type = ProductType::factory()->create();
        $resource = ProductTypeResource::make($type)->toArray(new Request);

        $this->assertArrayHasKey('id', $resource);
        $this->assertArrayHasKey('name', $resource);
        $this->assertArrayHasKey('is_active', $resource);
        $this->assertArrayHasKey('parent_id', $resource);
        $this->assertArrayHasKey('created_at', $resource);
        $this->assertArrayHasKey('updated_at', $resource);
    }

    public function test_id_is_uuid_not_integer(): void
    {
        $type = ProductType::factory()->create();
        $resource = ProductTypeResource::make($type)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }

    public function test_parent_id_is_null_for_root(): void
    {
        $type = ProductType::factory()->create();
        $resource = ProductTypeResource::make($type)->toArray(new Request);

        $this->assertNull($resource['parent_id']);
    }
}
