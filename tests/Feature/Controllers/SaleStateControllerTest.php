<?php

namespace Tests\Feature\Controllers;

use App\Models\SaleState;
use Tests\Feature\TenantFeatureTestCase;

class SaleStateControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_list(): void
    {
        SaleState::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sale-states')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/sale-states')->assertUnauthorized();
    }

    public function test_index_filters_by_search(): void
    {
        $match = SaleState::factory()->create(['name' => 'Pendiente Test']);
        SaleState::factory()->create(['name' => 'Entregado Test']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sale-states?search=Pendiente')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $match->name);
    }

    public function test_store_creates_sale_state(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sale-states', [
                'name' => 'En Proceso Test',
                'color' => '#ff5500',
                'is_default' => false,
                'is_final_state' => false,
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'En Proceso Test');

        $this->assertDatabaseHas('sale_states', ['name' => 'En Proceso Test'], 'tenant');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sale-states', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_sets_default_unsets_previous(): void
    {
        $existing = SaleState::factory()->default()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sale-states', [
                'name' => 'Nuevo Default Test',
                'is_default' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('sale_states', ['id' => $existing->id, 'is_default' => false], 'tenant');
    }

    public function test_update_modifies_sale_state(): void
    {
        $state = SaleState::factory()->create(['name' => 'Original Test', 'sort_order' => 0]);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/sale-states/{$state->uuid}", [
                'name' => 'Actualizado Test',
                'color' => '#0055ff',
                'is_default' => false,
                'is_final_state' => true,
                'is_active' => true,
                'sort_order' => 5,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Actualizado Test')
            ->assertJsonPath('data.is_final_state', true);
    }

    public function test_update_sets_default_unsets_previous(): void
    {
        $existing = SaleState::factory()->default()->create();
        $target = SaleState::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/sale-states/{$target->uuid}", [
                'name' => $target->name,
                'is_default' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('sale_states', ['id' => $existing->id, 'is_default' => false], 'tenant');
    }

    public function test_update_requires_auth(): void
    {
        $state = SaleState::factory()->create();

        $this->putJson("/api/sale-states/{$state->uuid}", ['name' => 'X'])
            ->assertUnauthorized();
    }

    public function test_destroy_soft_deletes(): void
    {
        $state = SaleState::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/sale-states/{$state->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('sale_states', ['id' => $state->id], 'tenant');
    }

    public function test_toggle_flips_is_active(): void
    {
        $state = SaleState::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/sale-states/{$state->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
