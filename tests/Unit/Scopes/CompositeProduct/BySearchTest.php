<?php

namespace Tests\Unit\Scopes\CompositeProduct;

use App\Models\CompositeProduct;
use App\Models\CompositeProduct\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        CompositeProduct::factory()->create(['name' => 'UNIQUE_SEARCHTERM_ALPHA']);
        CompositeProduct::factory()->create(['name' => 'Other Product']);

        $results = CompositeProduct::withScopes(new BySearch('UNIQUE_SEARCHTERM_ALPHA'))->get();

        $this->assertCount(1, $results);
        $this->assertSame('UNIQUE_SEARCHTERM_ALPHA', $results->first()->name);
    }

    public function test_filters_by_code(): void
    {
        CompositeProduct::factory()->create(['name' => 'Kit A', 'code' => 'KIT-001']);
        CompositeProduct::factory()->create(['name' => 'Kit B', 'code' => 'KIT-002']);

        $results = CompositeProduct::withScopes(new BySearch('KIT-001'))->get();

        $this->assertCount(1, $results);
        $this->assertSame('KIT-001', $results->first()->code);
    }

    public function test_returns_all_when_no_match(): void
    {
        CompositeProduct::factory()->count(2)->create();

        $results = CompositeProduct::withScopes(new BySearch('nonexistent_xyz'))->get();

        $this->assertCount(0, $results);
    }
}
