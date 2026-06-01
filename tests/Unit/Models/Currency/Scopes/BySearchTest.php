<?php

namespace Tests\Unit\Models\Currency\Scopes;

use App\Models\Currency;
use App\Models\Currency\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        Currency::factory()->create(['name' => 'Peso Argentino', 'symbol' => '$', 'iso_code' => 'ARS']);
        Currency::factory()->create(['name' => 'Dólar Estadounidense', 'symbol' => 'USD', 'iso_code' => 'USD']);

        $results = Currency::withScopes(new BySearch('Peso'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Peso Argentino', $results->first()->name);
    }

    public function test_filters_by_symbol(): void
    {
        Currency::factory()->create(['name' => 'Euro', 'symbol' => '€', 'iso_code' => 'EUR']);
        Currency::factory()->create(['name' => 'Peso', 'symbol' => '$', 'iso_code' => 'ARS']);

        $results = Currency::withScopes(new BySearch('€'))->get();

        $this->assertCount(1, $results);
    }

    public function test_filters_by_iso_code(): void
    {
        Currency::factory()->create(['name' => 'Dólar', 'symbol' => 'US$', 'iso_code' => 'USD']);
        Currency::factory()->create(['name' => 'Peso', 'symbol' => '$', 'iso_code' => 'ARS']);

        $results = Currency::withScopes(new BySearch('USD'))->get();

        $this->assertCount(1, $results);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Currency::factory()->create(['name' => 'Peso', 'symbol' => '$', 'iso_code' => 'ARS']);

        $results = Currency::withScopes(new BySearch('xyz_no_match'))->get();

        $this->assertCount(0, $results);
    }
}
