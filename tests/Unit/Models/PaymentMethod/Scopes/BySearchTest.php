<?php

namespace Tests\Unit\Models\PaymentMethod\Scopes;

use App\Models\PaymentMethod;
use App\Models\PaymentMethod\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        PaymentMethod::factory()->create(['name' => 'Efectivo']);
        PaymentMethod::factory()->create(['name' => 'Tarjeta de Crédito']);

        $results = PaymentMethod::withScopes(new BySearch('Efectivo'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Efectivo', $results->first()->name);
    }

    public function test_returns_empty_when_no_match(): void
    {
        PaymentMethod::factory()->create(['name' => 'Transferencia']);

        $results = PaymentMethod::withScopes(new BySearch('xyz_no_match'))->get();

        $this->assertCount(0, $results);
    }

    public function test_search_is_case_insensitive(): void
    {
        PaymentMethod::factory()->create(['name' => 'Mercado Pago']);

        $results = PaymentMethod::withScopes(new BySearch('mercado'))->get();

        $this->assertCount(1, $results);
    }
}
