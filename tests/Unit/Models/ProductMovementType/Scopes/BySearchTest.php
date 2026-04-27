<?php

namespace Tests\Unit\Models\ProductMovementType\Scopes;

use App\Models\ProductMovementType;
use App\Models\ProductMovementType\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        ProductMovementType::factory()->create(['name' => 'Ingreso por compra']);
        ProductMovementType::factory()->create(['name' => 'Ajuste inventario']);

        $results = ProductMovementType::withScopes(new BySearch('compra'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Ingreso por compra', $results->first()->name);
    }

    public function test_returns_empty_when_no_match(): void
    {
        ProductMovementType::factory()->create(['name' => 'Ingreso por compra']);

        $results = ProductMovementType::withScopes(new BySearch('xyz_no_match'))->get();

        $this->assertCount(0, $results);
    }

    public function test_search_is_case_insensitive(): void
    {
        ProductMovementType::factory()->create(['name' => 'Pérdida por rotura']);

        $results = ProductMovementType::withScopes(new BySearch('PÉRDIDA'))->get();

        $this->assertCount(1, $results);
    }
}
