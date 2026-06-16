<?php

namespace Tests\Unit\Scopes\UserReleaseRead;

use App\Models\User;
use App\Models\UserReleaseRead;
use App\Models\UserReleaseRead\Scopes\ByUser;
use Tests\Unit\Models\ModelTestCase;

class ByUserTest extends ModelTestCase
{
    public function test_filters_by_user_id(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $match = UserReleaseRead::factory()->create(['user_id' => $user->id]);
        UserReleaseRead::factory()->create(['user_id' => $other->id]);

        $results = UserReleaseRead::query()->withScopes(new ByUser($user->id))->get();

        $this->assertCount(1, $results);
        $this->assertSame($match->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        UserReleaseRead::factory()->create();

        $results = UserReleaseRead::query()->withScopes(new ByUser(99999))->get();

        $this->assertCount(0, $results);
    }
}
