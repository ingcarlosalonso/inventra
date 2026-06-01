<?php

namespace Tests\Unit\Scopes\DailyCash;

use App\Models\DailyCash;
use App\Models\DailyCash\Scopes\ByPointOfSale;
use App\Models\PointOfSale;
use Tests\Unit\Models\ModelTestCase;

class ByPointOfSaleTest extends ModelTestCase
{
    public function test_filters_by_point_of_sale_id(): void
    {
        $pos = PointOfSale::factory()->create();
        $other = PointOfSale::factory()->create();

        DailyCash::factory()->create(['point_of_sale_id' => $pos->id]);
        DailyCash::factory()->create(['point_of_sale_id' => $other->id]);

        $results = DailyCash::query()->withScopes(new ByPointOfSale($pos->id))->get();

        $this->assertCount(1, $results);
        $this->assertSame($pos->id, $results->first()->point_of_sale_id);
    }

    public function test_returns_empty_for_unknown_point_of_sale(): void
    {
        DailyCash::factory()->create();

        $results = DailyCash::query()->withScopes(new ByPointOfSale(99999))->get();

        $this->assertCount(0, $results);
    }
}
