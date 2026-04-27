<?php

namespace Tests\Feature\Controllers;

use App\Enums\SaleItemType;
use App\Models\CompositeProduct;
use App\Models\CompositeProductItem;
use App\Models\DailyCash;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Models\Sale;
use App\Models\SaleState;
use Tests\Feature\TenantFeatureTestCase;

class SaleControllerTest extends TenantFeatureTestCase
{
    private function productItem(ProductPresentation $pp, array $extra = []): array
    {
        return array_merge([
            'item_type' => SaleItemType::Product->value,
            'saleable_id' => $pp->uuid,
            'description' => 'Widget',
            'quantity' => 1,
            'unit_price' => 500,
        ], $extra);
    }

    private function validPayload(array $overrides = []): array
    {
        $pp = ProductPresentation::factory()->create(['stock' => 100, 'price' => 500]);
        $pos = PointOfSale::factory()->create();
        $pm = PaymentMethod::factory()->create();
        $state = SaleState::factory()->create(['is_default' => true]);

        return array_merge([
            'point_of_sale_id' => $pos->uuid,
            'sale_state_id' => $state->uuid,
            'items' => [$this->productItem($pp)],
            'payments' => [[
                'payment_method_id' => $pm->uuid,
                'amount' => 500,
            ]],
        ], $overrides);
    }

    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_list(): void
    {
        Sale::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sales')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/sales')->assertUnauthorized();
    }

    // ─── SHOW ────────────────────────────────────────────────────────────────

    public function test_show_returns_sale(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/sales/{$sale->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $sale->uuid);
    }

    public function test_show_returns_404_for_unknown(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sales/non-existent-uuid')
            ->assertNotFound();
    }

    public function test_show_requires_auth(): void
    {
        $sale = Sale::factory()->create();

        $this->getJson("/api/sales/{$sale->uuid}")->assertUnauthorized();
    }

    // ─── STORE — product items ────────────────────────────────────────────────

    public function test_store_creates_sale_and_decrements_stock(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $pos = PointOfSale::factory()->create();
        $pm = PaymentMethod::factory()->create();
        $state = SaleState::factory()->create(['is_default' => true]);

        $payload = [
            'point_of_sale_id' => $pos->uuid,
            'sale_state_id' => $state->uuid,
            'items' => [$this->productItem($pp, ['quantity' => 3, 'unit_price' => 100])],
            'payments' => [['payment_method_id' => $pm->uuid, 'amount' => 300]],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', $payload)
            ->assertCreated()
            ->assertJsonPath('data.total', 300)
            ->assertJsonPath('data.subtotal', 300);

        $this->assertEquals(7, $pp->fresh()->stock);
        $this->assertDatabaseHas('sales', ['total' => 300], 'tenant');
        $this->assertDatabaseHas('sale_items', ['quantity' => '3.000'], 'tenant');
        $this->assertDatabaseHas('payments', ['amount' => 300], 'tenant');
    }

    public function test_store_product_item_sets_saleable_morph(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $pos = PointOfSale::factory()->create();
        SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [$this->productItem($pp, ['quantity' => 1, 'unit_price' => 100])],
            ])
            ->assertCreated()
            ->assertJsonPath('data.items.0.item_type', 'product');

        $this->assertDatabaseHas('sale_items', [
            'saleable_type' => 'product_presentation',
            'saleable_id' => $pp->id,
            'product_presentation_id' => $pp->id,
        ], 'tenant');
    }

    public function test_store_applies_sale_percentage_discount(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 50]);
        $pos = PointOfSale::factory()->create();
        $pm = PaymentMethod::factory()->create();
        $state = SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'sale_state_id' => $state->uuid,
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'items' => [$this->productItem($pp, ['quantity' => 1, 'unit_price' => 200])],
                'payments' => [['payment_method_id' => $pm->uuid, 'amount' => 180]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal', 200)
            ->assertJsonPath('data.discount_amount', 20)
            ->assertJsonPath('data.total', 180);
    }

    public function test_store_uses_default_sale_state_when_not_provided(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $pos = PointOfSale::factory()->create();
        $pm = PaymentMethod::factory()->create();
        $defaultState = SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [$this->productItem($pp, ['quantity' => 1, 'unit_price' => 100])],
                'payments' => [['payment_method_id' => $pm->uuid, 'amount' => 100]],
            ])
            ->assertCreated();

        $sale = Sale::latest('id')->first();
        $this->assertTrue((bool) $sale->saleState->is_default);
    }

    public function test_store_returns_422_when_insufficient_stock(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 1]);
        $pos = PointOfSale::factory()->create();
        $pm = PaymentMethod::factory()->create();
        $state = SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'sale_state_id' => $state->uuid,
                'items' => [$this->productItem($pp, ['quantity' => 5, 'unit_price' => 100])],
                'payments' => [['payment_method_id' => $pm->uuid, 'amount' => 500]],
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    public function test_store_requires_point_of_sale(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'items' => [$this->productItem($pp)],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['point_of_sale_id']);
    }

    public function test_store_requires_items(): void
    {
        $pos = PointOfSale::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_store_allows_no_payments(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $pos = PointOfSale::factory()->create();
        SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [$this->productItem($pp, ['quantity' => 1, 'unit_price' => 100])],
            ])
            ->assertCreated();

        $this->assertDatabaseMissing('payments', ['payable_type' => 'sale'], 'tenant');
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/sales', [])->assertUnauthorized();
    }

    // ─── STORE — composite items ──────────────────────────────────────────────

    public function test_store_composite_item_decrements_component_stock(): void
    {
        $product = Product::factory()->create();
        $pp = ProductPresentation::factory()->create(['product_id' => $product->id, 'stock' => 20]);
        $composite = CompositeProduct::factory()->create();
        CompositeProductItem::factory()->create([
            'composite_product_id' => $composite->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $pos = PointOfSale::factory()->create();
        SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [[
                    'item_type' => SaleItemType::Composite->value,
                    'saleable_id' => $composite->uuid,
                    'description' => 'Kit',
                    'quantity' => 2,
                    'unit_price' => 500,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.items.0.item_type', 'composite');

        // 2 kits × 3 units each = 6 deducted from stock 20
        $this->assertEquals(14, $pp->fresh()->stock);
    }

    public function test_store_composite_item_sets_saleable_morph(): void
    {
        $composite = CompositeProduct::factory()->create();
        $pos = PointOfSale::factory()->create();
        SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [[
                    'item_type' => SaleItemType::Composite->value,
                    'saleable_id' => $composite->uuid,
                    'description' => 'Kit',
                    'quantity' => 1,
                    'unit_price' => 300,
                ]],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('sale_items', [
            'saleable_type' => 'composite_product',
            'saleable_id' => $composite->id,
            'product_presentation_id' => null,
        ], 'tenant');
    }

    public function test_store_composite_returns_422_when_component_stock_insufficient(): void
    {
        $product = Product::factory()->create();
        ProductPresentation::factory()->create(['product_id' => $product->id, 'stock' => 1]);
        $composite = CompositeProduct::factory()->create();
        CompositeProductItem::factory()->create([
            'composite_product_id' => $composite->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $pos = PointOfSale::factory()->create();
        SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [[
                    'item_type' => SaleItemType::Composite->value,
                    'saleable_id' => $composite->uuid,
                    'description' => 'Kit',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    // ─── STORE — promotion items ──────────────────────────────────────────────

    public function test_store_promotion_item_decrements_component_stock(): void
    {
        $product = Product::factory()->create();
        $pp = ProductPresentation::factory()->create(['product_id' => $product->id, 'stock' => 10]);
        $promotion = Promotion::factory()->create();
        PromotionItem::factory()->create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $pos = PointOfSale::factory()->create();
        SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [[
                    'item_type' => SaleItemType::Promotion->value,
                    'saleable_id' => $promotion->uuid,
                    'description' => 'Promo',
                    'quantity' => 3,
                    'unit_price' => 200,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.items.0.item_type', 'promotion');

        // 3 promos × 2 units each = 6 deducted from stock 10
        $this->assertEquals(4, $pp->fresh()->stock);
    }

    public function test_store_promotion_item_sets_saleable_morph(): void
    {
        $promotion = Promotion::factory()->create();
        $pos = PointOfSale::factory()->create();
        SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [[
                    'item_type' => SaleItemType::Promotion->value,
                    'saleable_id' => $promotion->uuid,
                    'description' => 'Promo',
                    'quantity' => 1,
                    'unit_price' => 150,
                ]],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('sale_items', [
            'saleable_type' => 'promotion',
            'saleable_id' => $promotion->id,
            'product_presentation_id' => null,
        ], 'tenant');
    }

    // ─── STORE — validation ───────────────────────────────────────────────────

    public function test_store_rejects_invalid_item_type(): void
    {
        $pos = PointOfSale::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [[
                    'item_type' => 'invalid_type',
                    'saleable_id' => 'some-uuid',
                    'description' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.item_type']);
    }

    public function test_store_rejects_nonexistent_saleable_id(): void
    {
        $pos = PointOfSale::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [[
                    'item_type' => SaleItemType::Product->value,
                    'saleable_id' => 'non-existent-uuid',
                    'description' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.saleable_id']);
    }

    public function test_store_rejects_duplicate_saleable_ids_of_same_type(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $pos = PointOfSale::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'items' => [
                    $this->productItem($pp),
                    $this->productItem($pp),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.1.saleable_id']);
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_sale(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/sales/{$sale->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted($sale);
    }

    public function test_destroy_returns_404_for_unknown(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/sales/non-existent-uuid')
            ->assertNotFound();
    }

    public function test_destroy_requires_auth(): void
    {
        $sale = Sale::factory()->create();

        $this->deleteJson("/api/sales/{$sale->uuid}")->assertUnauthorized();
    }

    // ─── PAID AMOUNT ─────────────────────────────────────────────────────────

    public function test_it_returns_paid_amount_in_index(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10, 'price' => 100]);
        $pos = PointOfSale::factory()->create();
        $pm = PaymentMethod::factory()->create();
        $state = SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'sale_state_id' => $state->uuid,
                'items' => [$this->productItem($pp, ['quantity' => 1, 'unit_price' => 100])],
                'payments' => [['payment_method_id' => $pm->uuid, 'amount' => 75.00]],
            ])
            ->assertCreated();

        $sale = Sale::latest('id')->first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sales')
            ->assertOk();

        $item = collect($response->json('data'))->firstWhere('id', $sale->uuid);
        $this->assertNotNull($item);
        $this->assertEquals(75.00, (float) $item['paid_amount']);
    }

    public function test_it_auto_links_payment_to_open_daily_cash(): void
    {
        $pos = PointOfSale::factory()->create();
        $dailyCash = DailyCash::factory()->create([
            'point_of_sale_id' => $pos->id,
            'opening_balance' => 0.00,
            'is_closed' => false,
        ]);

        $pp = ProductPresentation::factory()->create(['stock' => 10, 'price' => 100]);
        $pm = PaymentMethod::factory()->create();
        $state = SaleState::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'sale_state_id' => $state->uuid,
                'items' => [$this->productItem($pp, ['quantity' => 1, 'unit_price' => 100])],
                'payments' => [['payment_method_id' => $pm->uuid, 'amount' => 100.00]],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('payments', [
            'daily_cash_id' => $dailyCash->id,
            'amount' => 100.00,
        ], 'tenant');
    }
}
