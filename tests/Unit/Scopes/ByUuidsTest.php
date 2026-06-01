<?php

namespace Tests\Unit\Scopes;

use App\Models\Product;
use App\Models\Scopes\ByUuids;
use Tests\Unit\Models\ModelTestCase;

class ByUuidsTest extends ModelTestCase
{
    public function test_filters_by_multiple_uuids(): void
    {
        $a = Product::factory()->create();
        $b = Product::factory()->create();
        Product::factory()->create();

        $results = Product::query()->withScopes(new ByUuids([$a->uuid, $b->uuid]))->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains($a));
        $this->assertTrue($results->contains($b));
    }

    public function test_returns_empty_when_given_empty_array(): void
    {
        Product::factory()->create();

        $results = Product::query()->withScopes(new ByUuids([]))->get();

        $this->assertCount(0, $results);
    }
}
