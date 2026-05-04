<?php

namespace Tests\Unit\Scopes\DailyCash;

use App\Models\DailyCash;
use App\Models\DailyCash\Scopes\OpenedToday;
use Tests\Unit\Models\ModelTestCase;

class OpenedTodayTest extends ModelTestCase
{
    public function test_returns_only_daily_cashes_opened_today(): void
    {
        $today = DailyCash::factory()->create(['opened_at' => now()]);
        DailyCash::factory()->create(['opened_at' => now()->subDay()]);

        $results = DailyCash::query()->withScopes(new OpenedToday)->get();

        $this->assertCount(1, $results);
        $this->assertSame($today->id, $results->first()->id);
    }

    public function test_excludes_cashes_opened_yesterday(): void
    {
        DailyCash::factory()->create(['opened_at' => now()->subDay()]);

        $results = DailyCash::query()->withScopes(new OpenedToday)->get();

        $this->assertCount(0, $results);
    }
}
