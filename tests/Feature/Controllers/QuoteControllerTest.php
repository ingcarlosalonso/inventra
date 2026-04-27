<?php

namespace Tests\Feature\Controllers;

use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\ProductPresentation;
use App\Models\Quote;
use App\Models\SaleState;
use Tests\Feature\TenantFeatureTestCase;

class QuoteControllerTest extends TenantFeatureTestCase
{
    private function validPayload(array $overrides = []): array
    {
        $pp = ProductPresentation::factory()->create(['stock' => 50, 'price' => 200]);

        return array_merge([
            'starts_at' => '2026-04-13',
            'items' => [[
                'item_type' => 'product', 'saleable_id' => $pp->uuid,
                'description' => 'Widget',
                'quantity' => 2,
                'unit_price' => 200,
            ]],
        ], $overrides);
    }

    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_list(): void
    {
        Quote::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/quotes')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/quotes')->assertUnauthorized();
    }

    // ─── SHOW ────────────────────────────────────────────────────────────────

    public function test_show_returns_quote(): void
    {
        $quote = Quote::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/quotes/{$quote->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $quote->uuid);
    }

    public function test_show_returns_404_for_unknown(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/quotes/non-existent-uuid')
            ->assertNotFound();
    }

    public function test_show_requires_auth(): void
    {
        $quote = Quote::factory()->create();
        $this->getJson("/api/quotes/{$quote->uuid}")->assertUnauthorized();
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_quote_without_decrementing_stock(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);

        $payload = [
            'starts_at' => '2026-04-13',
            'expires_at' => '2026-04-30',
            'items' => [[
                'item_type' => 'product', 'saleable_id' => $pp->uuid,
                'description' => 'Test product',
                'quantity' => 5,
                'unit_price' => 100,
            ]],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/quotes', $payload)
            ->assertCreated()
            ->assertJsonPath('data.total', 500)
            ->assertJsonPath('data.is_converted', false);

        // Stock must NOT be decremented
        $this->assertEquals(10, $pp->fresh()->stock);
        $this->assertDatabaseHas('quotes', ['total' => 500], 'tenant');
    }

    public function test_store_applies_sale_level_discount(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 50]);

        $payload = [
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'items' => [[
                'item_type' => 'product', 'saleable_id' => $pp->uuid,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => 200,
            ]],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/quotes', $payload)
            ->assertCreated()
            ->assertJsonPath('data.subtotal', 200)
            ->assertJsonPath('data.discount_amount', 20)
            ->assertJsonPath('data.total', 180);
    }

    public function test_store_requires_items(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/quotes', ['items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_store_validates_expires_at_after_starts_at(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/quotes', [
                'starts_at' => '2026-04-30',
                'expires_at' => '2026-04-01',
                'items' => [[
                    'item_type' => 'product', 'saleable_id' => $pp->uuid,
                    'description' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['expires_at']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/quotes', [])->assertUnauthorized();
    }

    // ─── CONVERT ─────────────────────────────────────────────────────────────

    public function test_convert_creates_sale_and_decrements_stock(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $quote = Quote::factory()->create(['total' => 300]);
        $quote->items()->create([
            'product_presentation_id' => $pp->id,
            'description' => 'Test',
            'quantity' => 3,
            'unit_price' => 100,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => 300,
        ]);

        $pos = PointOfSale::factory()->create();
        $pm = PaymentMethod::factory()->create();
        SaleState::factory()->create(['is_default' => true]);

        $payload = [
            'point_of_sale_id' => $pos->uuid,
            'payments' => [['payment_method_id' => $pm->uuid, 'amount' => 300]],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/quotes/{$quote->uuid}/convert", $payload)
            ->assertCreated()
            ->assertJsonPath('data.total', 300);

        $this->assertEquals(7, $pp->fresh()->stock);
        $this->assertTrue($quote->fresh()->isConverted());
    }

    public function test_convert_returns_422_when_already_converted(): void
    {
        $quote = Quote::factory()->converted()->create();
        $pos = PointOfSale::factory()->create();
        $pm = PaymentMethod::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/quotes/{$quote->uuid}/convert", [
                'point_of_sale_id' => $pos->uuid,
                'payments' => [['payment_method_id' => $pm->uuid, 'amount' => 100]],
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    public function test_convert_requires_auth(): void
    {
        $quote = Quote::factory()->create();
        $this->postJson("/api/quotes/{$quote->uuid}/convert", [])->assertUnauthorized();
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_quote(): void
    {
        $quote = Quote::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/quotes/{$quote->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted($quote);
    }

    public function test_destroy_returns_404_for_unknown(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/quotes/non-existent-uuid')
            ->assertNotFound();
    }

    public function test_destroy_requires_auth(): void
    {
        $quote = Quote::factory()->create();
        $this->deleteJson("/api/quotes/{$quote->uuid}")->assertUnauthorized();
    }
}
