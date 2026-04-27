<?php

namespace Tests\Feature\Controllers;

use App\Models\Client;
use Tests\Feature\TenantFeatureTestCase;

class ClientControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_paginated_list(): void
    {
        Client::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/clients')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/clients')->assertUnauthorized();
    }

    public function test_store_creates_client(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', ['first_name' => 'Pedro', 'last_name' => 'García', 'email' => 'pedro@email.com'])
            ->assertCreated()
            ->assertJsonPath('data.first_name', 'Pedro')
            ->assertJsonPath('data.last_name', 'García')
            ->assertJsonPath('data.full_name', 'Pedro García');

        $this->assertDatabaseHas('clients', ['first_name' => 'Pedro', 'last_name' => 'García'], 'tenant');
    }

    public function test_store_validates_first_name_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', ['last_name' => 'García'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name']);
    }

    public function test_store_validates_last_name_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', ['first_name' => 'Pedro'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['last_name']);
    }

    public function test_update_modifies_client(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/clients/{$client->uuid}", ['first_name' => 'Nuevo', 'last_name' => 'Nombre', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.full_name', 'Nuevo Nombre');
    }

    public function test_destroy_soft_deletes(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/clients/{$client->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('clients', ['id' => $client->id], 'tenant');
    }
}
