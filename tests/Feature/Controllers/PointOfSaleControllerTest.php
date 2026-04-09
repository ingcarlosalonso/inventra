<?php

namespace Tests\Feature\Controllers;

use App\Models\PointOfSale;
use Tests\Feature\TenantFeatureTestCase;

class PointOfSaleControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_list(): void
    {
        PointOfSale::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/points-of-sale')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/points-of-sale')->assertUnauthorized();
    }

    public function test_index_filters_by_search(): void
    {
        $match = PointOfSale::factory()->create(['name' => 'Sucursal Norte Test', 'number' => 10]);
        PointOfSale::factory()->create(['name' => 'Sucursal Sur Test', 'number' => 11]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/points-of-sale?search=Norte')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $match->name);
    }

    public function test_store_creates_point_of_sale(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/points-of-sale', [
                'number' => 42,
                'name' => 'Casa Central Test',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Casa Central Test')
            ->assertJsonPath('data.number', 42);

        $this->assertDatabaseHas('points_of_sale', ['name' => 'Casa Central Test'], 'tenant');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/points-of-sale', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['number', 'name']);
    }

    public function test_store_validates_unique_number(): void
    {
        PointOfSale::factory()->create(['number' => 99]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/points-of-sale', ['number' => 99, 'name' => 'Duplicado Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['number']);
    }

    public function test_update_modifies_point_of_sale(): void
    {
        $pos = PointOfSale::factory()->create(['number' => 5, 'name' => 'Original Test']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/points-of-sale/{$pos->uuid}", [
                'number' => 5,
                'name' => 'Actualizado Test',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Actualizado Test')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_update_requires_auth(): void
    {
        $pos = PointOfSale::factory()->create();

        $this->putJson("/api/points-of-sale/{$pos->uuid}", ['number' => 1, 'name' => 'X'])
            ->assertUnauthorized();
    }

    public function test_update_returns_404_for_missing(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/points-of-sale/non-existent-uuid', ['number' => 1, 'name' => 'X'])
            ->assertNotFound();
    }

    public function test_destroy_soft_deletes(): void
    {
        $pos = PointOfSale::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/points-of-sale/{$pos->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('points_of_sale', ['id' => $pos->id], 'tenant');
    }

    public function test_toggle_flips_is_active(): void
    {
        $pos = PointOfSale::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/points-of-sale/{$pos->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_toggle_activates_inactive(): void
    {
        $pos = PointOfSale::factory()->create(['is_active' => false]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/points-of-sale/{$pos->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    }
}
