<?php

namespace Tests\Unit\Scopes\Promotion;

use App\Models\Promotion;
use App\Models\Promotion\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        Promotion::factory()->create(['name' => 'UNIQUE_PROMO_SEARCHTERM_ALPHA']);
        Promotion::factory()->create(['name' => 'Other Promotion']);

        $results = Promotion::withScopes(new BySearch('UNIQUE_PROMO_SEARCHTERM_ALPHA'))->get();

        $this->assertCount(1, $results);
        $this->assertSame('UNIQUE_PROMO_SEARCHTERM_ALPHA', $results->first()->name);
    }

    public function test_filters_by_code(): void
    {
        Promotion::factory()->create(['name' => 'Promo A', 'code' => 'PROMO-001']);
        Promotion::factory()->create(['name' => 'Promo B', 'code' => 'PROMO-002']);

        $results = Promotion::withScopes(new BySearch('PROMO-001'))->get();

        $this->assertCount(1, $results);
        $this->assertSame('PROMO-001', $results->first()->code);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Promotion::factory()->count(2)->create();

        $results = Promotion::withScopes(new BySearch('nonexistent_xyz'))->get();

        $this->assertCount(0, $results);
    }
}
