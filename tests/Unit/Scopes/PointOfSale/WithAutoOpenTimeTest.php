<?php

namespace Tests\Unit\Scopes\PointOfSale;

use App\Models\PointOfSale;
use App\Models\PointOfSale\Scopes\WithAutoOpenTime;
use Tests\Unit\Models\ModelTestCase;

class WithAutoOpenTimeTest extends ModelTestCase
{
    public function test_includes_point_of_sale_with_auto_open_time(): void
    {
        $withTime = PointOfSale::factory()->create(['auto_open_time' => '08:00:00']);
        $withoutTime = PointOfSale::factory()->create(['auto_open_time' => null]);

        $results = PointOfSale::query()->withScopes(new WithAutoOpenTime)->get();

        $this->assertTrue($results->contains($withTime));
        $this->assertFalse($results->contains($withoutTime));
    }

    public function test_returns_empty_when_none_have_auto_open_time(): void
    {
        PointOfSale::factory()->create(['auto_open_time' => null]);

        $results = PointOfSale::query()->withScopes(new WithAutoOpenTime)->get();

        $this->assertCount(0, $results);
    }
}
