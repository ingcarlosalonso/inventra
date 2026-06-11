<?php

namespace Tests\Unit\Models\Supplier\Scopes;

use App\Models\Supplier;
use App\Models\Supplier\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        Supplier::factory()->create(['name' => 'Distribuidora Norte', 'email' => 'a@a.com']);
        Supplier::factory()->create(['name' => 'Importadora Sur', 'email' => 'b@b.com']);

        $results = Supplier::withScopes(new BySearch('Norte'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Distribuidora Norte', $results->first()->name);
    }

    public function test_filters_by_email(): void
    {
        Supplier::factory()->create(['name' => 'Proveedor A', 'email' => 'contacto@proveedor.com']);
        Supplier::factory()->create(['name' => 'Proveedor B', 'email' => 'ventas@otro.com']);

        $results = Supplier::withScopes(new BySearch('proveedor.com'))->get();

        $this->assertCount(1, $results);
    }

    public function test_filters_by_contact_name(): void
    {
        Supplier::factory()->create(['name' => 'A', 'contact_name' => 'Juan Pérez', 'email' => 'c@c.com']);
        Supplier::factory()->create(['name' => 'B', 'contact_name' => 'María García', 'email' => 'd@d.com']);

        $results = Supplier::withScopes(new BySearch('Juan'))->get();

        $this->assertCount(1, $results);
    }
}
