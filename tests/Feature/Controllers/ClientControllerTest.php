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
            ->postJson('/api/clients', ['name' => 'Pedro García', 'email' => 'pedro@email.com'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Pedro García');

        $this->assertDatabaseHas('clients', ['name' => 'Pedro García'], 'tenant');
    }

    public function test_store_validates_name_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_modifies_client(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/clients/{$client->uuid}", ['name' => 'Nombre Nuevo', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
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
