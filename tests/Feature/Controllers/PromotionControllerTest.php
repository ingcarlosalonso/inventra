<?php

namespace Tests\Feature\Controllers;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionItem;
use Tests\Feature\TenantFeatureTestCase;

class PromotionControllerTest extends TenantFeatureTestCase
{
    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_list(): void
    {
        Promotion::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/promotions')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_filters_by_name(): void
    {
        Promotion::factory()->create(['name' => 'UNIQUE_PROMOVERANO_XYZ']);
        Promotion::factory()->create(['name' => 'Descuento Invierno']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/promotions?search=UNIQUE_PROMOVERANO_XYZ')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_code(): void
    {
        Promotion::factory()->create(['name' => 'Promo A', 'code' => 'UNIQUEPROMO-001-ABC']);
        Promotion::factory()->create(['name' => 'Promo B', 'code' => 'UNIQUEPROMO-002-ABC']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/promotions?search=UNIQUEPROMO-001-ABC')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/promotions')->assertUnauthorized();
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_promotion(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/promotions', [
                'name' => '2x1 Test',
                'items' => [
                    ['product_id' => $product->uuid, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', '2x1 Test');

        $this->assertDatabaseHas('promotions', ['name' => '2x1 Test'], 'tenant');
    }

    public function test_store_creates_promotion_with_sale_price(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/promotions', [
                'name' => 'Promo con Precio',
                'sale_price' => 199.99,
                'items' => [
                    ['product_id' => $product->uuid, 'quantity' => 1],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.sale_price', '199.99');
    }

    public function test_store_creates_items(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/promotions', [
                'name' => 'Promo Multi',
                'items' => [
                    ['product_id' => $product1->uuid, 'quantity' => 1],
                    ['product_id' => $product2->uuid, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'data.items');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/promotions', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'items']);
    }

    public function test_store_validates_items_not_empty(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/promotions', [
                'name' => 'Promo Vacía',
                'items' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_store_validates_item_quantity_min_1(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/promotions', [
                'name' => 'Promo Inválida',
                'items' => [
                    ['product_id' => $product->uuid, 'quantity' => 0],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/promotions', [])->assertUnauthorized();
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function test_update_modifies_promotion(): void
    {
        $promotion = Promotion::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/promotions/{$promotion->uuid}", [
                'name' => 'Nombre Actualizado',
                'items' => [
                    ['product_id' => $product->uuid, 'quantity' => 1],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre Actualizado');
    }

    public function test_update_syncs_items(): void
    {
        $promotion = Promotion::factory()->create();
        $oldProduct = Product::factory()->create();
        PromotionItem::factory()->create([
            'promotion_id' => $promotion->id,
            'product_id' => $oldProduct->id,
        ]);

        $newProduct = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/promotions/{$promotion->uuid}", [
                'name' => $promotion->name,
                'items' => [
                    ['product_id' => $newProduct->uuid, 'quantity' => 3],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_update_returns_404_for_missing(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/promotions/non-existent-uuid', [])
            ->assertNotFound();
    }

    // ─── TOGGLE ──────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $promotion = Promotion::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/promotions/{$promotion->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes(): void
    {
        $promotion = Promotion::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/promotions/{$promotion->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('promotions', ['id' => $promotion->id], 'tenant');
    }

    public function test_destroy_returns_404_for_missing(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/promotions/non-existent-uuid')
            ->assertNotFound();
    }
}
