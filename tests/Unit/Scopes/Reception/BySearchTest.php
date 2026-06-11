<?php

namespace Tests\Unit\Scopes\Reception;

use App\Models\Reception;
use App\Models\Reception\Scopes\BySearch;
use App\Models\Supplier;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_supplier_invoice(): void
    {
        Reception::factory()->create(['supplier_invoice' => 'FAC-0001']);
        Reception::factory()->create(['supplier_invoice' => 'FAC-0002']);

        $results = Reception::query()->withScopes(new BySearch('FAC-0001'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('FAC-0001', $results->first()->supplier_invoice);
    }

    public function test_filters_by_notes(): void
    {
        Reception::factory()->create(['notes' => 'Pedido urgente']);
        Reception::factory()->create(['notes' => 'Sin observaciones']);

        $results = Reception::query()->withScopes(new BySearch('urgente'))->get();

        $this->assertCount(1, $results);
    }

    public function test_filters_by_supplier_name(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Proveedor Especial']);
        Reception::factory()->create(['supplier_id' => $supplier->id]);
        Reception::factory()->create(['supplier_id' => Supplier::factory()->create(['name' => 'Otro'])->id]);

        $results = Reception::query()->withScopes(new BySearch('Especial'))->get();

        $this->assertCount(1, $results);
    }
}
