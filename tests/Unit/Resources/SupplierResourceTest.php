<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\Supplier\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class SupplierResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $supplier = Supplier::factory()->create();
        $resource = SupplierResource::make($supplier)->toArray(new Request);

        foreach (['id', 'name', 'contact_name', 'email', 'phone', 'address', 'notes', 'is_active', 'created_at', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $resource);
        }
    }

    public function test_id_is_uuid(): void
    {
        $supplier = Supplier::factory()->create();
        $resource = SupplierResource::make($supplier)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }
}
