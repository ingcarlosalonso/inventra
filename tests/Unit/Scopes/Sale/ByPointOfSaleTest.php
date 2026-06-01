<?php

namespace Tests\Unit\Scopes\Sale;

use App\Models\PointOfSale;
use App\Models\Sale;
use App\Models\Sale\Scopes\ByPointOfSale;
use Tests\Unit\Models\ModelTestCase;

class ByPointOfSaleTest extends ModelTestCase
{
    public function test_filters_by_point_of_sale_id(): void
    {
        $pos = PointOfSale::factory()->create();
        $other = PointOfSale::factory()->create();

        $match = Sale::factory()->create(['point_of_sale_id' => $pos->id]);
        Sale::factory()->create(['point_of_sale_id' => $other->id]);

        $results = Sale::query()->withScopes(new ByPointOfSale($pos->id))->get();

        $this->assertCount(1, $results);
        $this->assertSame($match->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Sale::factory()->create();

        $results = Sale::query()->withScopes(new ByPointOfSale(99999))->get();

        $this->assertCount(0, $results);
    }
}
