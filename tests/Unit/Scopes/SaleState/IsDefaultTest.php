<?php

namespace Tests\Unit\Scopes\SaleState;

use App\Models\SaleState;
use App\Models\SaleState\Scopes\IsDefault;
use Tests\Unit\Models\ModelTestCase;

class IsDefaultTest extends ModelTestCase
{
    public function test_returns_only_default_state(): void
    {
        SaleState::factory()->create(['is_default' => false]);
        $default = SaleState::factory()->create(['is_default' => true]);

        $results = SaleState::query()->withScopes(new IsDefault)->get();

        $this->assertCount(1, $results);
        $this->assertSame($default->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_default_exists(): void
    {
        SaleState::factory()->create(['is_default' => false]);

        $results = SaleState::query()->withScopes(new IsDefault)->get();

        $this->assertCount(0, $results);
    }
}
