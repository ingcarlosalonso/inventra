<?php

namespace Tests\Unit\Models\CashMovementType\Scopes;

use App\Models\CashMovementType;
use App\Models\CashMovementType\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        CashMovementType::factory()->create(['name' => 'Venta efectivo']);
        CashMovementType::factory()->create(['name' => 'Depósito bancario']);

        $results = CashMovementType::withScopes(new BySearch('efectivo'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Venta efectivo', $results->first()->name);
    }

    public function test_returns_empty_when_no_match(): void
    {
        CashMovementType::factory()->create(['name' => 'Venta efectivo']);

        $results = CashMovementType::withScopes(new BySearch('xyz_no_match'))->get();

        $this->assertCount(0, $results);
    }

    public function test_search_is_case_insensitive(): void
    {
        CashMovementType::factory()->create(['name' => 'Retiro Caja']);

        $results = CashMovementType::withScopes(new BySearch('retiro'))->get();

        $this->assertCount(1, $results);
    }
}
