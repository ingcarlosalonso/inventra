<?php

namespace Tests\Unit\Scopes\OrderState;

use App\Models\OrderState;
use App\Models\OrderState\Scopes\IsDefault;
use Tests\Unit\Models\ModelTestCase;

class IsDefaultTest extends ModelTestCase
{
    public function test_returns_only_default_state(): void
    {
        OrderState::factory()->create(['is_default' => false]);
        $default = OrderState::factory()->create(['is_default' => true]);

        $results = OrderState::query()->withScopes(new IsDefault)->get();

        $this->assertCount(1, $results);
        $this->assertSame($default->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_default_exists(): void
    {
        OrderState::factory()->create(['is_default' => false]);

        $results = OrderState::query()->withScopes(new IsDefault)->get();

        $this->assertCount(0, $results);
    }
}
