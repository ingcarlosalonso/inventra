<?php

namespace Tests\Feature\Controllers;

use App\Models\DailyCash;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\ProductPresentation;
use Tests\Feature\TenantFeatureTestCase;

class OrderControllerTest extends TenantFeatureTestCase
{
    private function validPayload(array $overrides = []): array
    {
        $pp = ProductPresentation::factory()->create(['stock' => 100, 'price' => 500]);
        $state = OrderState::factory()->create(['is_default' => true]);

        return array_merge([
            'order_state_id' => $state->uuid,
            'items' => [[
                'item_type' => 'product', 'saleable_id' => $pp->uuid,
                'description' => 'Widget',
                'quantity' => 2,
                'unit_price' => 500,
            ]],
        ], $overrides);
    }

    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_list(): void
    {
        Order::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    // ─── SHOW ────────────────────────────────────────────────────────────────

    public function test_show_returns_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orders/{$order->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->uuid);
    }

    public function test_show_returns_404_for_unknown(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders/non-existent-uuid')
            ->assertNotFound();
    }

    public function test_show_requires_auth(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/orders/{$order->uuid}")->assertUnauthorized();
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_order_and_decrements_stock(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $state = OrderState::factory()->create(['is_default' => true]);

        $payload = [
            'order_state_id' => $state->uuid,
            'items' => [[
                'item_type' => 'product', 'saleable_id' => $pp->uuid,
                'description' => 'Test product',
                'quantity' => 3,
                'unit_price' => 100,
            ]],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', $payload)
            ->assertCreated()
            ->assertJsonPath('data.total', 300)
            ->assertJsonPath('data.subtotal', 300);

        $this->assertEquals(7, $pp->fresh()->stock);
        $this->assertDatabaseHas('orders', ['total' => 300], 'tenant');
        $this->assertDatabaseHas('order_items', ['quantity' => '3.000'], 'tenant');
    }

    public function test_store_creates_order_with_delivery_data(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $state = OrderState::factory()->create(['is_default' => true]);

        $payload = [
            'order_state_id' => $state->uuid,
            'address' => '123 Main St',
            'requires_delivery' => true,
            'delivery_date' => '2026-05-01',
            'items' => [[
                'item_type' => 'product', 'saleable_id' => $pp->uuid,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => 50,
            ]],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', $payload)
            ->assertCreated()
            ->assertJsonPath('data.requires_delivery', true)
            ->assertJsonPath('data.delivery_date', '2026-05-01')
            ->assertJsonPath('data.address', '123 Main St');
    }

    public function test_store_with_payment_creates_payment_record(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $state = OrderState::factory()->create(['is_default' => true]);
        $pm = PaymentMethod::factory()->create();

        $payload = [
            'order_state_id' => $state->uuid,
            'items' => [[
                'item_type' => 'product', 'saleable_id' => $pp->uuid,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => 100,
            ]],
            'payments' => [[
                'payment_method_id' => $pm->uuid,
                'amount' => 100,
            ]],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('payments', ['amount' => 100], 'tenant');
    }

    public function test_store_uses_default_order_state_when_not_provided(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $defaultState = OrderState::factory()->create(['is_default' => true]);

        $payload = [
            'items' => [[
                'item_type' => 'product', 'saleable_id' => $pp->uuid,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => 100,
            ]],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', $payload)
            ->assertCreated();

        $order = Order::latest('id')->first();
        $this->assertTrue((bool) $order->orderState->is_default);
    }

    public function test_store_returns_422_when_insufficient_stock(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 1]);
        $state = OrderState::factory()->create(['is_default' => true]);

        $payload = [
            'order_state_id' => $state->uuid,
            'items' => [[
                'item_type' => 'product', 'saleable_id' => $pp->uuid,
                'description' => 'Test',
                'quantity' => 5,
                'unit_price' => 100,
            ]],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    public function test_store_requires_items(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', ['items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/orders', [])->assertUnauthorized();
    }

    // ─── UPDATE STATE ────────────────────────────────────────────────────────

    public function test_update_state_changes_order_state(): void
    {
        $order = Order::factory()->create();
        $newState = OrderState::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/orders/{$order->uuid}/state", [
                'order_state_id' => $newState->uuid,
            ])
            ->assertOk()
            ->assertJsonPath('data.order_state.id', $newState->uuid);
    }

    public function test_update_state_requires_auth(): void
    {
        $order = Order::factory()->create();
        $state = OrderState::factory()->create();

        $this->patchJson("/api/orders/{$order->uuid}/state", [
            'order_state_id' => $state->uuid,
        ])->assertUnauthorized();
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/orders/{$order->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted($order);
    }

    public function test_destroy_returns_404_for_unknown(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/orders/non-existent-uuid')
            ->assertNotFound();
    }

    public function test_destroy_requires_auth(): void
    {
        $order = Order::factory()->create();

        $this->deleteJson("/api/orders/{$order->uuid}")->assertUnauthorized();
    }

    // ─── PAID AMOUNT ─────────────────────────────────────────────────────────

    public function test_it_returns_paid_amount_in_index(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10, 'price' => 100]);
        $state = OrderState::factory()->create(['is_default' => true]);
        $pm = PaymentMethod::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'order_state_id' => $state->uuid,
                'items' => [[
                    'item_type' => 'product', 'saleable_id' => $pp->uuid,
                    'description' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]],
                'payments' => [[
                    'payment_method_id' => $pm->uuid,
                    'amount' => 60.00,
                ]],
            ])
            ->assertCreated();

        $order = Order::latest('id')->first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders')
            ->assertOk();

        $item = collect($response->json('data'))->firstWhere('id', $order->uuid);
        $this->assertNotNull($item);
        $this->assertEquals(60.00, (float) $item['paid_amount']);
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
        $state = OrderState::factory()->create(['is_default' => true]);
        $pm = PaymentMethod::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'order_state_id' => $state->uuid,
                'point_of_sale_id' => $pos->uuid,
                'items' => [[
                    'item_type' => 'product', 'saleable_id' => $pp->uuid,
                    'description' => 'Widget',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]],
                'payments' => [[
                    'payment_method_id' => $pm->uuid,
                    'amount' => 100.00,
                ]],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('payments', [
            'daily_cash_id' => $dailyCash->id,
            'amount' => 100.00,
        ], 'tenant');
    }
}
