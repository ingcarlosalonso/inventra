<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\ProductMovementType\ProductMovementTypeResource;
use App\Models\ProductMovementType;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class ProductMovementTypeResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $type = ProductMovementType::factory()->create();
        $resource = ProductMovementTypeResource::make($type)->toArray(new Request);

        foreach (['id', 'name', 'is_income', 'is_active', 'created_at', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $resource);
        }
    }

    public function test_id_is_uuid(): void
    {
        $type = ProductMovementType::factory()->create();
        $resource = ProductMovementTypeResource::make($type)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }

    public function test_is_income_is_boolean(): void
    {
        $type = ProductMovementType::factory()->outgoing()->create();
        $resource = ProductMovementTypeResource::make($type)->toArray(new Request);

        $this->assertFalse($resource['is_income']);
    }
}
