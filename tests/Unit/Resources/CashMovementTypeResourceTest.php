<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\CashMovementType\CashMovementTypeResource;
use App\Models\CashMovementType;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class CashMovementTypeResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $type = CashMovementType::factory()->create();
        $resource = CashMovementTypeResource::make($type)->toArray(new Request);

        foreach (['id', 'name', 'is_income', 'is_active', 'created_at', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $resource);
        }
    }

    public function test_id_is_uuid(): void
    {
        $type = CashMovementType::factory()->create();
        $resource = CashMovementTypeResource::make($type)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }

    public function test_is_income_is_boolean(): void
    {
        $type = CashMovementType::factory()->expense()->create();
        $resource = CashMovementTypeResource::make($type)->toArray(new Request);

        $this->assertFalse($resource['is_income']);
    }
}
