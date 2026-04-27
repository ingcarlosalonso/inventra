<?php

namespace Tests\Feature\Controllers;

use App\Models\ProductPresentation;
use App\Models\Reception;
use App\Models\Supplier;
use Tests\Feature\TenantFeatureTestCase;

class ReceptionControllerTest extends TenantFeatureTestCase
{
    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_list(): void
    {
        Reception::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/receptions')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_filters_by_supplier_invoice(): void
    {
        Reception::factory()->create(['supplier_invoice' => 'FAC-0001']);
        Reception::factory()->create(['supplier_invoice' => 'FAC-0002']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/receptions?search=FAC-0001')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/receptions')->assertUnauthorized();
    }

    // ─── SHOW ────────────────────────────────────────────────────────────────

    public function test_show_returns_reception(): void
    {
        $reception = Reception::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/receptions/{$reception->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $reception->uuid);
    }

    public function test_show_returns_404_for_unknown(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/receptions/non-existent-uuid')
            ->assertNotFound();
    }

    public function test_show_requires_auth(): void
    {
        $reception = Reception::factory()->create();

        $this->getJson("/api/receptions/{$reception->uuid}")->assertUnauthorized();
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_reception_and_increments_stock(): void
    {
        $pp = ProductPresentation::factory()->create(['stock' => 10]);
        $supplier = Supplier::factory()->create();

        $payload = [
            'supplier_id' => $supplier->uuid,
            'received_at' => '2026-04-13',
            'supplier_invoice' => 'FAC-0042',
            'items' => [
                [
                    'product_presentation_id' => $pp->uuid,
                    'quantity' => 5,
                    'unit_cost' => 200,
                ],
            ],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/receptions', $payload)
            ->assertCreated()
            ->assertJsonPath('data.supplier_invoice', 'FAC-0042')
            ->assertJsonPath('data.total', 1000);

        $this->assertEquals(15, $pp->fresh()->stock);
        $this->assertDatabaseHas('receptions', ['supplier_invoice' => 'FAC-0042'], 'tenant');
        $this->assertDatabaseHas('reception_items', ['quantity' => '5.000'], 'tenant');
    }

    public function test_store_calculates_total_from_items(): void
    {
        $pp1 = ProductPresentation::factory()->create(['stock' => 0]);
        $pp2 = ProductPresentation::factory()->create(['stock' => 0]);

        $payload = [
            'received_at' => '2026-04-13',
            'items' => [
                ['product_presentation_id' => $pp1->uuid, 'quantity' => 2, 'unit_cost' => 100],
                ['product_presentation_id' => $pp2->uuid, 'quantity' => 3, 'unit_cost' => 50],
            ],
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/receptions', $payload)
            ->assertCreated()
            ->assertJsonPath('data.total', 350);
    }

    public function test_store_requires_received_at(): void
    {
        $pp = ProductPresentation::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/receptions', [
                'items' => [['product_presentation_id' => $pp->uuid, 'quantity' => 1, 'unit_cost' => 50]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['received_at']);
    }

    public function test_store_requires_items(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/receptions', [
                'received_at' => '2026-04-13',
                'items' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/receptions', [])->assertUnauthorized();
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_reception(): void
    {
        $reception = Reception::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/receptions/{$reception->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted($reception);
    }

    public function test_destroy_returns_404_for_unknown(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/receptions/non-existent-uuid')
            ->assertNotFound();
    }

    public function test_destroy_requires_auth(): void
    {
        $reception = Reception::factory()->create();

        $this->deleteJson("/api/receptions/{$reception->uuid}")->assertUnauthorized();
    }
}
