<?php

namespace Tests\Feature\Controllers;

use App\Models\Courier;
use Tests\Feature\TenantFeatureTestCase;

class CourierControllerTest extends TenantFeatureTestCase
{
    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_all_couriers(): void
    {
        Courier::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/orders/couriers')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/orders/couriers')->assertUnauthorized();
    }

    public function test_index_filters_by_search(): void
    {
        Courier::factory()->create(['name' => 'Juan García']);
        Courier::factory()->create(['name' => 'Pedro López']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/orders/couriers?search=Juan')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_courier(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/orders/couriers', [
                'name' => 'Carlos Romero',
                'phone' => '1234567890',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Carlos Romero');

        $this->assertDatabaseHas('couriers', ['name' => 'Carlos Romero'], 'tenant');
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/orders/couriers', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/v1/orders/couriers', ['name' => 'Test'])->assertUnauthorized();
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function test_update_modifies_courier(): void
    {
        $courier = Courier::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/orders/couriers/{$courier->uuid}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_update_requires_auth(): void
    {
        $courier = Courier::factory()->create();

        $this->putJson("/api/v1/orders/couriers/{$courier->uuid}", ['name' => 'X'])->assertUnauthorized();
    }

    // ─── TOGGLE ──────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $courier = Courier::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/orders/couriers/{$courier->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_courier(): void
    {
        $courier = Courier::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/orders/couriers/{$courier->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted($courier);
    }

    public function test_destroy_requires_auth(): void
    {
        $courier = Courier::factory()->create();

        $this->deleteJson("/api/v1/orders/couriers/{$courier->uuid}")->assertUnauthorized();
    }
}
