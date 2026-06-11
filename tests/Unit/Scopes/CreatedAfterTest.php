<?php

namespace Tests\Unit\Scopes;

use App\Models\Sale;
use App\Models\Scopes\CreatedAfter;
use Illuminate\Support\Carbon;
use Tests\Unit\Models\ModelTestCase;

class CreatedAfterTest extends ModelTestCase
{
    public function test_includes_records_created_on_or_after_the_date(): void
    {
        $recent = Sale::factory()->create(['created_at' => Carbon::now()]);
        $old = Sale::factory()->create(['created_at' => Carbon::yesterday()]);

        $results = Sale::query()->withScopes(new CreatedAfter(Carbon::today()))->get();

        $this->assertTrue($results->contains($recent));
        $this->assertFalse($results->contains($old));
    }

    public function test_excludes_records_created_before_the_date(): void
    {
        $old = Sale::factory()->create(['created_at' => Carbon::yesterday()]);

        $results = Sale::query()->withScopes(new CreatedAfter(Carbon::today()))->get();

        $this->assertFalse($results->contains($old));
    }
}
