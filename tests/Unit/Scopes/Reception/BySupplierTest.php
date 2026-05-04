<?php

namespace Tests\Unit\Scopes\Reception;

use App\Models\Reception;
use App\Models\Reception\Scopes\BySupplier;
use App\Models\Supplier;
use Tests\Unit\Models\ModelTestCase;

class BySupplierTest extends ModelTestCase
{
    public function test_filters_by_supplier_id(): void
    {
        $supplier = Supplier::factory()->create();
        $other = Supplier::factory()->create();

        $match = Reception::factory()->create(['supplier_id' => $supplier->id]);
        Reception::factory()->create(['supplier_id' => $other->id]);

        $results = Reception::query()->withScopes(new BySupplier($supplier->id))->get();

        $this->assertCount(1, $results);
        $this->assertSame($match->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Reception::factory()->create();

        $results = Reception::query()->withScopes(new BySupplier(99999))->get();

        $this->assertCount(0, $results);
    }
}
