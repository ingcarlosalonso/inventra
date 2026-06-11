<?php

namespace Tests\Unit\Scopes;

use App\Models\Product;
use App\Models\Scopes\ByUuid;
use Tests\Unit\Models\ModelTestCase;

class ByUuidTest extends ModelTestCase
{
    public function test_filters_by_uuid(): void
    {
        $product = Product::factory()->create();
        Product::factory()->create();

        $results = Product::query()->withScopes(new ByUuid($product->uuid))->get();

        $this->assertCount(1, $results);
        $this->assertSame($product->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Product::factory()->create();

        $results = Product::query()->withScopes(new ByUuid('non-existent-uuid'))->get();

        $this->assertCount(0, $results);
    }
}
