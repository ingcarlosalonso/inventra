<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\Reception\ReceptionResource;
use App\Models\Reception;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class ReceptionResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $reception = Reception::factory()->create();
        $resource = ReceptionResource::make($reception)->toArray(new Request);

        $this->assertArrayHasKey('id', $resource);
        $this->assertArrayHasKey('supplier_invoice', $resource);
        $this->assertArrayHasKey('total', $resource);
        $this->assertArrayHasKey('notes', $resource);
        $this->assertArrayHasKey('received_at', $resource);
        $this->assertArrayHasKey('created_at', $resource);
        $this->assertArrayHasKey('updated_at', $resource);
    }

    public function test_id_is_uuid_not_integer(): void
    {
        $reception = Reception::factory()->create();
        $resource = ReceptionResource::make($reception)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }

    public function test_supplier_included_when_loaded(): void
    {
        $reception = Reception::factory()->create();
        $reception->load('supplier');
        $resource = ReceptionResource::make($reception)->toArray(new Request);

        $this->assertArrayHasKey('supplier', $resource);
        $this->assertArrayHasKey('id', $resource['supplier']);
        $this->assertArrayHasKey('name', $resource['supplier']);
    }

    public function test_items_included_when_loaded(): void
    {
        $reception = Reception::factory()->create();
        $reception->load('items');
        $resource = ReceptionResource::make($reception)->toArray(new Request);

        $this->assertArrayHasKey('items', $resource);
    }
}
