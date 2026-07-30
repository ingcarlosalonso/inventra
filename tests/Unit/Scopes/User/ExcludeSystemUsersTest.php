<?php

namespace Tests\Unit\Scopes\User;

use App\Models\User;
use App\Models\User\Scopes\ExcludeSystemUsers;
use Tests\Unit\Models\ModelTestCase;

class ExcludeSystemUsersTest extends ModelTestCase
{
    public function test_regular_users_are_visible_by_default(): void
    {
        $user = User::factory()->create(['is_system' => false]);

        $this->assertTrue(User::all()->contains($user));
    }

    public function test_system_users_are_excluded_from_default_queries(): void
    {
        $systemUser = User::factory()->create(['is_system' => true]);

        $this->assertFalse(User::all()->contains($systemUser));
    }

    public function test_system_users_are_visible_when_scope_is_removed(): void
    {
        $systemUser = User::factory()->create(['is_system' => true]);

        $results = User::withoutGlobalScope(ExcludeSystemUsers::class)->get();

        $this->assertTrue($results->contains($systemUser));
    }

    public function test_system_user_does_not_increase_visible_count(): void
    {
        $countBefore = User::count();

        User::factory()->create(['is_system' => false]);
        User::factory()->create(['is_system' => true]);

        $this->assertSame($countBefore + 1, User::count());
    }
}
