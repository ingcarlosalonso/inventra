<?php

namespace Tests\Feature\Controllers;

use App\Models\Brand;
use Tests\Feature\TenantFeatureTestCase;

class BrandControllerTest extends TenantFeatureTestCase
{
    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_list(): void
    {
        Brand::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products/brands')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'is_active']]]);
    }

    public function test_index_filters_by_search(): void
    {
        Brand::factory()->create(['name' => 'Coca Cola']);
        Brand::factory()->create(['name' => 'Pepsi']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products/brands?search=Coca')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/products/brands')->assertUnauthorized();
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_brand(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products/brands', ['name' => 'Nike'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Nike')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('brands', ['name' => 'Nike'], 'tenant');
    }

    public function test_store_validates_name_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products/brands', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_name_unique(): void
    {
        Brand::factory()->create(['name' => 'Adidas']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products/brands', ['name' => 'Adidas'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/v1/products/brands', ['name' => 'Test'])->assertUnauthorized();
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function test_update_modifies_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/brands/{$brand->uuid}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('brands', ['name' => 'New Name'], 'tenant');
    }

    public function test_update_allows_same_name_on_self(): void
    {
        $brand = Brand::factory()->create(['name' => 'Samsung']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/brands/{$brand->uuid}", ['name' => 'Samsung'])
            ->assertOk();
    }

    public function test_update_returns_404_for_unknown_brand(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/products/brands/non-existent-uuid', ['name' => 'X'])
            ->assertNotFound();
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_deletes_brand(): void
    {
        $brand = Brand::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/brands/{$brand->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('brands', ['id' => $brand->id], 'tenant');
    }

    public function test_destroy_returns_404_for_unknown_brand(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/products/brands/non-existent-uuid')
            ->assertNotFound();
    }

    // ─── TOGGLE ──────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $brand = Brand::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/brands/{$brand->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'is_active' => false], 'tenant');
    }
}
