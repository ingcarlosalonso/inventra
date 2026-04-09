<?php

namespace Tests\Unit\Models\SaleState\Scopes;

use App\Models\SaleState;
use App\Models\SaleState\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        SaleState::factory()->create(['name' => 'Pendiente']);
        SaleState::factory()->create(['name' => 'Entregado']);

        $results = SaleState::withScopes(new BySearch('Pendiente'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Pendiente', $results->first()->name);
    }

    public function test_returns_empty_when_no_match(): void
    {
        SaleState::factory()->create(['name' => 'En Proceso']);

        $results = SaleState::withScopes(new BySearch('xyz_no_match'))->get();

        $this->assertCount(0, $results);
    }

    public function test_search_is_case_insensitive(): void
    {
        SaleState::factory()->create(['name' => 'Cancelado']);

        $results = SaleState::withScopes(new BySearch('cancelado'))->get();

        $this->assertCount(1, $results);
    }
}
