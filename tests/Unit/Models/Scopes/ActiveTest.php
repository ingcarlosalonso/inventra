<?php

namespace Tests\Unit\Models\Scopes;

use App\Models\Currency;
use App\Models\Scopes\Active;
use Tests\Unit\Models\ModelTestCase;

class ActiveTest extends ModelTestCase
{
    public function test_filters_active_records(): void
    {
        $active = Currency::factory()->create(['is_active' => true]);
        $inactive = Currency::factory()->create(['is_active' => false]);

        $results = Currency::withScopes(new Active)->get();

        $this->assertTrue($results->contains('id', $active->id));
        $this->assertFalse($results->contains('id', $inactive->id));
    }

    public function test_excludes_inactive_records(): void
    {
        $inactive1 = Currency::factory()->create(['is_active' => false]);
        $inactive2 = Currency::factory()->create(['is_active' => false]);

        $results = Currency::withScopes(new Active)->pluck('id');

        $this->assertNotContains($inactive1->id, $results->all());
        $this->assertNotContains($inactive2->id, $results->all());
    }

    public function test_all_results_are_active(): void
    {
        Currency::factory()->count(2)->create(['is_active' => true]);
        Currency::factory()->count(2)->create(['is_active' => false]);

        $results = Currency::withScopes(new Active)->get();

        $this->assertTrue($results->every(fn ($c) => $c->is_active));
    }
}
