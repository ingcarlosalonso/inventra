<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Tests\Feature\TenantFeatureTestCase;

class UserControllerTest extends TenantFeatureTestCase
{
    // ── index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_list(): void
    {
        $before = User::count();
        User::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/settings/users')
            ->assertOk()
            ->assertJsonCount($before + 3, 'data');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/settings/users')->assertUnauthorized();
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_user(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/settings/users', [
                'name' => 'New User',
                'email' => 'new.user@example.com',
                'password' => 'Password123',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'New User');

        $this->assertDatabaseHas('users', ['email' => 'new.user@example.com'], 'tenant');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/settings/users', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_update_modifies_user(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/settings/users/{$user->uuid}", [
                'name' => 'Updated Name',
                'email' => $user->email,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/settings/users/{$user->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $user->id], 'tenant');
    }

    public function test_destroy_blocks_self_deletion(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/settings/users/{$this->user->uuid}")
            ->assertUnprocessable()
            ->assertJsonPath('message', __('users.cannot_delete_self'));

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'deleted_at' => null], 'tenant');
    }

    // ── toggle ────────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/settings/users/{$user->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_toggle_blocks_self_deactivation(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/settings/users/{$this->user->uuid}/toggle")
            ->assertUnprocessable()
            ->assertJsonPath('message', __('users.cannot_deactivate_self'));

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'is_active' => true], 'tenant');
    }
}
