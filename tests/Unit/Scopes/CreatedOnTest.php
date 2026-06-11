<?php

namespace Tests\Unit\Scopes;

use App\Models\Sale;
use App\Models\Scopes\CreatedOn;
use Illuminate\Support\Carbon;
use Tests\Unit\Models\ModelTestCase;

class CreatedOnTest extends ModelTestCase
{
    public function test_returns_only_records_created_on_the_given_date(): void
    {
        $today = Sale::factory()->create(['created_at' => Carbon::today()->setTime(14, 0)]);
        $yesterday = Sale::factory()->create(['created_at' => Carbon::yesterday()]);

        $results = Sale::query()->withScopes(new CreatedOn(Carbon::today()))->get();

        $this->assertTrue($results->contains($today));
        $this->assertFalse($results->contains($yesterday));
    }

    public function test_excludes_records_not_created_on_the_given_date(): void
    {
        $yesterday = Sale::factory()->create(['created_at' => Carbon::yesterday()]);

        $results = Sale::query()->withScopes(new CreatedOn(Carbon::today()))->get();

        $this->assertFalse($results->contains($yesterday));
    }
}
