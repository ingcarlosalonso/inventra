<?php

namespace Tests\Feature\Controllers;

use App\Models\Permission;
use Tests\Feature\TenantFeatureTestCase;

class PermissionControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_all_permissions(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/settings/permissions')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name']]])
            ->assertJsonCount(Permission::count(), 'data');
    }

    public function test_index_returns_permissions_ordered_by_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/settings/permissions')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertEquals($names->sort()->values()->all(), $names->values()->all());
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/settings/permissions')->assertUnauthorized();
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->userWithoutPermissions(), 'sanctum')
            ->getJson('/api/v1/settings/permissions')
            ->assertForbidden();
    }

    public function test_index_requires_create_edit_delete_roles_permission_not_just_list(): void
    {
        $user = $this->userWithPermissions('list_roles');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/settings/permissions')
            ->assertForbidden();
    }
}
