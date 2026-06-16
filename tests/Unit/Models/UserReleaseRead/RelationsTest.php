<?php

namespace Tests\Unit\Models\UserReleaseRead;

use App\Models\User;
use App\Models\UserReleaseRead;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $read = UserReleaseRead::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $read->user);
        $this->assertSame($user->id, $read->user->id);
    }
}
