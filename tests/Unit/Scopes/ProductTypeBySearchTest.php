<?php

namespace Tests\Unit\Scopes;

use App\Models\ProductType;
use App\Models\ProductType\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class ProductTypeBySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        ProductType::factory()->create(['name' => 'Electrónica']);
        ProductType::factory()->create(['name' => 'Ropa']);

        $results = ProductType::withScopes(new BySearch('Electr'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Electrónica', $results->first()->name);
    }

    public function test_returns_all_when_no_match(): void
    {
        ProductType::factory()->create(['name' => 'Alimentos']);

        $results = ProductType::withScopes(new BySearch('xyz_no_match'))->get();

        $this->assertCount(0, $results);
    }

    public function test_search_is_case_insensitive(): void
    {
        ProductType::factory()->create(['name' => 'Electrónica']);

        $results = ProductType::withScopes(new BySearch('electrónica'))->get();

        $this->assertCount(1, $results);
    }
}
