<?php

namespace Tests\Feature\Controllers;

use App\Models\CompositeProduct;
use App\Models\CompositeProductItem;
use App\Models\Product;
use Tests\Feature\TenantFeatureTestCase;

class CompositeProductControllerTest extends TenantFeatureTestCase
{
    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_list(): void
    {
        CompositeProduct::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/composite-products')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_filters_by_name(): void
    {
        CompositeProduct::factory()->create(['name' => 'UNIQUE_KITVERANO_XYZ']);
        CompositeProduct::factory()->create(['name' => 'Pack Invierno']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/composite-products?search=UNIQUE_KITVERANO_XYZ')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_code(): void
    {
        CompositeProduct::factory()->create(['name' => 'Kit A', 'code' => 'UNIQUEKIT-001-ABC']);
        CompositeProduct::factory()->create(['name' => 'Kit B', 'code' => 'UNIQUEKIT-002-ABC']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/composite-products?search=UNIQUEKIT-001-ABC')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/composite-products')->assertUnauthorized();
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_composite_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/composite-products', [
                'name' => 'Kit Test',
                'items' => [
                    ['product_id' => $product->uuid, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Kit Test');

        $this->assertDatabaseHas('composite_products', ['name' => 'Kit Test'], 'tenant');
    }

    public function test_store_creates_items(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/composite-products', [
                'name' => 'Kit Multi',
                'items' => [
                    ['product_id' => $product1->uuid, 'quantity' => 1],
                    ['product_id' => $product2->uuid, 'quantity' => 3],
                ],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'data.items');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/composite-products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'items']);
    }

    public function test_store_validates_items_not_empty(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/composite-products', [
                'name' => 'Kit Vacío',
                'items' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_store_validates_item_quantity_min_1(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/composite-products', [
                'name' => 'Kit Inválido',
                'items' => [
                    ['product_id' => $product->uuid, 'quantity' => 0],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/composite-products', [])->assertUnauthorized();
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function test_update_modifies_composite_product(): void
    {
        $compositeProduct = CompositeProduct::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/composite-products/{$compositeProduct->uuid}", [
                'name' => 'Nombre Actualizado',
                'items' => [
                    ['product_id' => $product->uuid, 'quantity' => 5],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre Actualizado');
    }

    public function test_update_syncs_items(): void
    {
        $compositeProduct = CompositeProduct::factory()->create();
        $oldProduct = Product::factory()->create();
        CompositeProductItem::factory()->create([
            'composite_product_id' => $compositeProduct->id,
            'product_id' => $oldProduct->id,
        ]);

        $newProduct = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/composite-products/{$compositeProduct->uuid}", [
                'name' => $compositeProduct->name,
                'items' => [
                    ['product_id' => $newProduct->uuid, 'quantity' => 2],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_update_returns_404_for_missing(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/composite-products/non-existent-uuid', [])
            ->assertNotFound();
    }

    // ─── TOGGLE ──────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $compositeProduct = CompositeProduct::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/composite-products/{$compositeProduct->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes(): void
    {
        $compositeProduct = CompositeProduct::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/composite-products/{$compositeProduct->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('composite_products', ['id' => $compositeProduct->id], 'tenant');
    }

    public function test_destroy_returns_404_for_missing(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/composite-products/non-existent-uuid')
            ->assertNotFound();
    }
}
