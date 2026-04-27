<?php

namespace Tests\Unit\Models\PointOfSale\Scopes;

use App\Models\PointOfSale;
use App\Models\PointOfSale\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        PointOfSale::factory()->create(['name' => 'Sucursal Norte', 'number' => 1]);
        PointOfSale::factory()->create(['name' => 'Casa Central', 'number' => 2]);

        $results = PointOfSale::withScopes(new BySearch('Norte'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Sucursal Norte', $results->first()->name);
    }

    public function test_filters_by_address(): void
    {
        PointOfSale::factory()->create(['name' => 'POS One', 'number' => 3, 'address' => 'Av. Corrientes 123']);
        PointOfSale::factory()->create(['name' => 'POS Two', 'number' => 4, 'address' => 'Calle Falsa 742']);

        $results = PointOfSale::withScopes(new BySearch('Corrientes'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('POS One', $results->first()->name);
    }

    public function test_returns_empty_when_no_match(): void
    {
        PointOfSale::factory()->create(['name' => 'Sucursal Sur', 'number' => 5]);

        $results = PointOfSale::withScopes(new BySearch('xyz_no_match'))->get();

        $this->assertCount(0, $results);
    }

    public function test_search_is_case_insensitive(): void
    {
        PointOfSale::factory()->create(['name' => 'Sucursal Este', 'number' => 6]);

        $results = PointOfSale::withScopes(new BySearch('este'))->get();

        $this->assertCount(1, $results);
    }
}
