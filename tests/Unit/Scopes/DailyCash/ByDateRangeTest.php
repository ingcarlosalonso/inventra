<?php

namespace Tests\Unit\Scopes\DailyCash;

use App\Models\DailyCash;
use App\Models\DailyCash\Scopes\ByDateRange;
use Illuminate\Support\Carbon;
use Tests\Unit\Models\ModelTestCase;

class ByDateRangeTest extends ModelTestCase
{
    public function test_includes_records_within_range(): void
    {
        $inside = DailyCash::factory()->create(['opened_at' => Carbon::yesterday()]);
        $outside = DailyCash::factory()->create(['opened_at' => Carbon::now()->subDays(10)]);

        $from = Carbon::now()->subDays(3);
        $to = Carbon::now();

        $results = DailyCash::query()->withScopes(new ByDateRange($from, $to))->get();

        $this->assertTrue($results->contains($inside));
        $this->assertFalse($results->contains($outside));
    }

    public function test_returns_empty_when_no_records_in_range(): void
    {
        DailyCash::factory()->create(['opened_at' => Carbon::now()->subDays(10)]);

        $from = Carbon::now()->subDays(2);
        $to = Carbon::now();

        $results = DailyCash::query()->withScopes(new ByDateRange($from, $to))->get();

        $this->assertCount(0, $results);
    }
}
