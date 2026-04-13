<?php

namespace Tests\Feature\Controllers;

use App\Models\Barcode;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\ProductType;
use Tests\Feature\TenantFeatureTestCase;

class ProductControllerTest extends TenantFeatureTestCase
{
    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_list(): void
    {
        Product::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/products')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_filters_by_name(): void
    {
        Product::factory()->create(['name' => 'Rosa Roja']);
        Product::factory()->create(['name' => 'Tulipán Blanco']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/products?search=Rosa')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_barcode(): void
    {
        $product = Product::factory()->create();
        Barcode::factory()->create(['product_id' => $product->id, 'barcode' => '7794000012345']);
        Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/products?search=7794000012345')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_includes_presentations(): void
    {
        $product = Product::factory()->create();
        ProductPresentation::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/products')
            ->assertOk();

        $response->assertJsonStructure(['data' => [['presentations']]]);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_product(): void
    {
        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Producto Test',
                'product_type_id' => $productType->uuid,
                'presentations' => [
                    ['presentation_id' => $presentation->uuid, 'price' => 150.50, 'min_stock' => 5],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Producto Test');

        $this->assertDatabaseHas('products', ['name' => 'Producto Test'], 'tenant');
    }

    public function test_store_creates_product_presentations(): void
    {
        $productType = ProductType::factory()->create();
        $presentation1 = Presentation::factory()->create();
        $presentation2 = Presentation::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Yerba Mate',
                'product_type_id' => $productType->uuid,
                'presentations' => [
                    ['presentation_id' => $presentation1->uuid, 'price' => 100, 'min_stock' => 5],
                    ['presentation_id' => $presentation2->uuid, 'price' => 180, 'min_stock' => 3],
                ],
            ])
            ->assertCreated();

        $response->assertJsonCount(2, 'data.presentations');
    }

    public function test_store_creates_product_with_barcodes(): void
    {
        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Producto con Códigos',
                'product_type_id' => $productType->uuid,
                'presentations' => [
                    ['presentation_id' => $presentation->uuid, 'price' => 100, 'min_stock' => 1],
                ],
                'barcodes' => ['111222333444', '555666777888'],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'data.barcodes');

        $this->assertDatabaseHas('barcodes', ['barcode' => '111222333444'], 'tenant');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'product_type_id', 'presentations']);
    }

    public function test_store_validates_presentations_not_empty(): void
    {
        $productType = ProductType::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Test',
                'product_type_id' => $productType->uuid,
                'presentations' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presentations']);
    }

    public function test_store_validates_duplicate_barcode(): void
    {
        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();
        $existing = Product::factory()->create();
        Barcode::factory()->create(['product_id' => $existing->id, 'barcode' => 'DUPLICADO']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Nuevo Producto',
                'product_type_id' => $productType->uuid,
                'presentations' => [
                    ['presentation_id' => $presentation->uuid, 'price' => 100, 'min_stock' => 1],
                ],
                'barcodes' => ['DUPLICADO'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['barcodes.0']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/products', [])->assertUnauthorized();
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function test_update_modifies_product(): void
    {
        $product = Product::factory()->create();
        $presentation = Presentation::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/products/{$product->uuid}", [
                'name' => 'Nombre Actualizado',
                'product_type_id' => $product->productType->uuid,
                'presentations' => [
                    ['presentation_id' => $presentation->uuid, 'price' => 200, 'min_stock' => 10],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre Actualizado');
    }

    public function test_update_syncs_presentations(): void
    {
        $product = Product::factory()->create();
        $oldPresentation = Presentation::factory()->create();
        $newPresentation = Presentation::factory()->create();
        ProductPresentation::factory()->create([
            'product_id' => $product->id,
            'presentation_id' => $oldPresentation->id,
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/products/{$product->uuid}", [
                'name' => $product->name,
                'product_type_id' => $product->productType->uuid,
                'presentations' => [
                    ['presentation_id' => $newPresentation->uuid, 'price' => 150, 'min_stock' => 2],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.presentations');

        $this->assertSoftDeleted('product_presentations', [
            'product_id' => $product->id,
            'presentation_id' => $oldPresentation->id,
        ], 'tenant');
    }

    public function test_update_syncs_barcodes(): void
    {
        $product = Product::factory()->create();
        $presentation = Presentation::factory()->create();
        Barcode::factory()->create(['product_id' => $product->id, 'barcode' => 'OLD-CODE']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/products/{$product->uuid}", [
                'name' => $product->name,
                'product_type_id' => $product->productType->uuid,
                'presentations' => [
                    ['presentation_id' => $presentation->uuid, 'price' => 100, 'min_stock' => 1],
                ],
                'barcodes' => ['NEW-CODE'],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.barcodes')
            ->assertJsonFragment(['NEW-CODE']);

        $this->assertDatabaseMissing('barcodes', ['barcode' => 'OLD-CODE'], 'tenant');
    }

    public function test_update_returns_404_for_missing(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/products/non-existent-uuid', [])
            ->assertNotFound();
    }

    // ─── TOGGLE ──────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/products/{$product->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/products/{$product->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id], 'tenant');
    }

    public function test_destroy_returns_404_for_missing(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/products/non-existent-uuid')
            ->assertNotFound();
    }
}
