<?php

namespace Tests\Feature\Controllers;

use App\Models\ProductType;
use Tests\Feature\TenantFeatureTestCase;

class ProductTypeControllerTest extends TenantFeatureTestCase
{
    // ── index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_list(): void
    {
        $before = ProductType::count();
        ProductType::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products/types')
            ->assertOk()
            ->assertJsonCount($before + 3, 'data');
    }

    public function test_index_filters_by_search(): void
    {
        ProductType::factory()->create(['name' => 'Electrónica']);
        ProductType::factory()->create(['name' => 'Ropa']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products/types?search=Electr');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Electrónica');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/products/types')->assertUnauthorized();
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_product_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products/types', ['name' => 'Nuevo Tipo', 'is_active' => true]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Nuevo Tipo')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('product_types', ['name' => 'Nuevo Tipo'], 'tenant');
    }

    public function test_store_validates_name_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products/types', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_unique_name(): void
    {
        ProductType::factory()->create(['name' => 'Duplicado']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products/types', ['name' => 'Duplicado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_update_modifies_product_type(): void
    {
        $type = ProductType::factory()->create(['name' => 'Original']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/types/{$type->uuid}", ['name' => 'Modificado', 'is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.name', 'Modificado');
    }

    public function test_update_rejects_self_as_parent(): void
    {
        $type = ProductType::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/types/{$type->uuid}", [
                'name' => $type->name,
                'is_active' => true,
                'parent_id' => $type->id,
            ])
            ->assertStatus(422);
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes(): void
    {
        $type = ProductType::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/types/{$type->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('product_types', ['id' => $type->id], 'tenant');
    }

    public function test_destroy_rejects_type_with_children(): void
    {
        $parent = ProductType::factory()->create();
        ProductType::factory()->childOf($parent)->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/types/{$parent->uuid}")
            ->assertStatus(422);
    }

    // ── toggle ────────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $type = ProductType::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/types/{$type->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
