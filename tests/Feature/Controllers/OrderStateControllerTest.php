<?php

namespace Tests\Feature\Controllers;

use App\Models\OrderState;
use Tests\Feature\TenantFeatureTestCase;

class OrderStateControllerTest extends TenantFeatureTestCase
{
    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_all_order_states(): void
    {
        OrderState::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/order-states')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/order-states')->assertUnauthorized();
    }

    public function test_index_filters_by_search(): void
    {
        OrderState::factory()->create(['name' => 'Pendiente']);
        OrderState::factory()->create(['name' => 'Entregado']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/order-states?search=Pend')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_order_state(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/order-states', ['name' => 'Nuevo Estado'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Nuevo Estado');

        $this->assertDatabaseHas('order_states', ['name' => 'Nuevo Estado'], 'tenant');
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/order-states', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/order-states', ['name' => 'Test'])->assertUnauthorized();
    }

    public function test_store_unsets_previous_default_when_setting_new_default(): void
    {
        $first = OrderState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/order-states', ['name' => 'Default 2', 'is_default' => true])
            ->assertCreated();

        $this->assertFalse((bool) $first->fresh()->is_default);
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function test_update_modifies_order_state(): void
    {
        $state = OrderState::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/order-states/{$state->uuid}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_update_requires_auth(): void
    {
        $state = OrderState::factory()->create();

        $this->putJson("/api/order-states/{$state->uuid}", ['name' => 'X'])->assertUnauthorized();
    }

    // ─── TOGGLE ──────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $state = OrderState::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/order-states/{$state->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_order_state(): void
    {
        $state = OrderState::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/order-states/{$state->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted($state);
    }

    public function test_destroy_requires_auth(): void
    {
        $state = OrderState::factory()->create();

        $this->deleteJson("/api/order-states/{$state->uuid}")->assertUnauthorized();
    }
}
