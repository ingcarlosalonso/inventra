<?php

namespace Tests\Unit\Scopes\Quote;

use App\Models\Quote;
use App\Models\Quote\Scopes\NotConverted;
use App\Models\Sale;
use Tests\Unit\Models\ModelTestCase;

class NotConvertedTest extends ModelTestCase
{
    public function test_returns_quotes_without_sale(): void
    {
        $pending = Quote::factory()->create(['sale_id' => null]);
        $converted = Quote::factory()->create(['sale_id' => Sale::factory()->create()->id]);

        $results = Quote::query()->withScopes(new NotConverted)->get();

        $this->assertTrue($results->contains($pending));
        $this->assertFalse($results->contains($converted));
    }

    public function test_returns_empty_when_all_converted(): void
    {
        Quote::factory()->create(['sale_id' => Sale::factory()->create()->id]);

        $results = Quote::query()->withScopes(new NotConverted)->get();

        $this->assertCount(0, $results);
    }
}
