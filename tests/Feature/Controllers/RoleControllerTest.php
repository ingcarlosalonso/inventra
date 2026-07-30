<?php

namespace Tests\Feature\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Tests\Feature\TenantFeatureTestCase;

class RoleControllerTest extends TenantFeatureTestCase
{
    // ── INDEX ─────────────────────────────────────────────────────────────────

    public function test_index_returns_roles_list(): void
    {
        Role::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/settings/roles')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'permissions_count']]]);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/settings/roles')->assertUnauthorized();
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->userWithoutPermissions(), 'sanctum')
            ->getJson('/api/v1/settings/roles')
            ->assertForbidden();
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────

    public function test_show_returns_role_with_permissions(): void
    {
        $permission = Permission::first();
        $role = Role::factory()->create();
        $role->givePermissionTo($permission);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/settings/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('data.name', $role->name)
            ->assertJsonStructure(['data' => ['id', 'name', 'permissions']]);
    }

    public function test_show_requires_permission(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->userWithoutPermissions(), 'sanctum')
            ->getJson("/api/v1/settings/roles/{$role->id}")
            ->assertForbidden();
    }

    public function test_show_returns_404_for_unknown_role(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/settings/roles/99999')
            ->assertNotFound();
    }

    // ── STORE ─────────────────────────────────────────────────────────────────

    public function test_store_creates_role(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/settings/roles', ['name' => 'Supervisor'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Supervisor');

        $this->assertDatabaseHas('roles', ['name' => 'Supervisor'], 'tenant');
    }

    public function test_store_creates_role_with_permissions(): void
    {
        $permission = Permission::first();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/settings/roles', [
                'name' => 'Supervisor',
                'permissions' => [$permission->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Supervisor');

        $role = Role::where('name', 'Supervisor')->first();
        $this->assertTrue($role->hasPermissionTo($permission));
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/settings/roles', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_requires_permission(): void
    {
        $this->actingAs($this->userWithoutPermissions(), 'sanctum')
            ->postJson('/api/v1/settings/roles', ['name' => 'Bloqueado'])
            ->assertForbidden();
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────

    public function test_update_renames_role(): void
    {
        $role = Role::factory()->create(['name' => 'Original']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/settings/roles/{$role->id}", [
                'name' => 'Renombrado',
                'permissions' => [],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renombrado');
    }

    public function test_update_syncs_permissions(): void
    {
        $role = Role::factory()->create();
        $permissions = Permission::take(2)->get();
        $role->givePermissionTo($permissions->first());

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/settings/roles/{$role->id}", [
                'name' => $role->name,
                'permissions' => [$permissions->last()->id],
            ])
            ->assertOk();

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo($permissions->first()));
        $this->assertTrue($role->hasPermissionTo($permissions->last()));
    }

    public function test_update_requires_permission(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->userWithoutPermissions(), 'sanctum')
            ->putJson("/api/v1/settings/roles/{$role->id}", ['name' => 'Bloqueado', 'permissions' => []])
            ->assertForbidden();
    }

    // ── DESTROY ───────────────────────────────────────────────────────────────

    public function test_destroy_deletes_role(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/settings/roles/{$role->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('roles', ['id' => $role->id], 'tenant');
    }

    public function test_destroy_requires_permission(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->userWithoutPermissions(), 'sanctum')
            ->deleteJson("/api/v1/settings/roles/{$role->id}")
            ->assertForbidden();
    }

    public function test_destroy_returns_404_for_unknown_role(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/settings/roles/99999')
            ->assertNotFound();
    }
}
