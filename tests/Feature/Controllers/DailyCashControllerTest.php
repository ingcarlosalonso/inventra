<?php

namespace Tests\Feature\Controllers;

use App\Models\CashMovement;
use App\Models\CashMovementType;
use App\Models\DailyCash;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use Tests\Feature\TenantFeatureTestCase;

class DailyCashControllerTest extends TenantFeatureTestCase
{
    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_list(): void
    {
        DailyCash::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/daily-cashes')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/daily-cashes')->assertUnauthorized();
    }

    public function test_it_lists_daily_cashes_with_current_balance(): void
    {
        // Use a fresh PoS with no prior daily cash data to avoid pollution from other tests
        $pos = PointOfSale::factory()->create();
        $dailyCash = DailyCash::factory()->create([
            'point_of_sale_id' => $pos->id,
            'opening_balance' => 200.00,
        ]);
        $pm = PaymentMethod::factory()->create();

        Payment::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'payable_type' => 'sale',
            'payment_method_id' => $pm->id,
            'amount' => 100.00,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/daily-cashes')
            ->assertOk();

        // Find the specific daily cash in the paginated response and assert its balance
        $item = collect($response->json('data'))
            ->firstWhere('id', $dailyCash->uuid);

        $this->assertNotNull($item);
        $this->assertEquals(300.00, (float) $item['current_balance']);
    }

    // ─── SHOW ────────────────────────────────────────────────────────────────

    public function test_show_returns_daily_cash(): void
    {
        $dailyCash = DailyCash::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/daily-cashes/{$dailyCash->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $dailyCash->uuid);
    }

    public function test_show_returns_404_for_unknown(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/daily-cashes/non-existent-uuid')
            ->assertNotFound();
    }

    public function test_show_requires_auth(): void
    {
        $dailyCash = DailyCash::factory()->create();

        $this->getJson("/api/daily-cashes/{$dailyCash->uuid}")->assertUnauthorized();
    }

    public function test_it_shows_daily_cash_with_current_balance(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 500.00]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/daily-cashes/{$dailyCash->uuid}")
            ->assertOk();

        $this->assertEquals(500.00, (float) $response->json('data.current_balance'));
    }

    public function test_it_shows_current_balance_includes_sale_payments(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 100.00]);
        $pm = PaymentMethod::factory()->create();

        Payment::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'payable_type' => 'sale',
            'payment_method_id' => $pm->id,
            'amount' => 400.00,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/daily-cashes/{$dailyCash->uuid}")
            ->assertOk();

        $this->assertEquals(500.00, (float) $response->json('data.current_balance'));
    }

    public function test_it_shows_current_balance_includes_cash_movements(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 200.00]);
        $incomeType = CashMovementType::factory()->create(['is_income' => true]);
        $expenseType = CashMovementType::factory()->expense()->create();

        CashMovement::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'cash_movement_type_id' => $incomeType->id,
            'amount' => 150.00,
        ]);

        CashMovement::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'cash_movement_type_id' => $expenseType->id,
            'amount' => 50.00,
        ]);

        // Expected: 200 + 150 - 50 = 300
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/daily-cashes/{$dailyCash->uuid}")
            ->assertOk();

        $this->assertEquals(300.00, (float) $response->json('data.current_balance'));
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function test_store_creates_daily_cash(): void
    {
        $pos = PointOfSale::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/daily-cashes', [
                'point_of_sale_id' => $pos->uuid,
                'opening_balance' => 1000.00,
            ])
            ->assertCreated();

        $this->assertEquals(1000.00, (float) $response->json('data.opening_balance'));
        $this->assertDatabaseHas('daily_cashes', ['opening_balance' => 1000.00], 'tenant');
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/daily-cashes', [])->assertUnauthorized();
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function test_destroy_deletes_open_daily_cash(): void
    {
        $dailyCash = DailyCash::factory()->create(['is_closed' => false]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/daily-cashes/{$dailyCash->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted($dailyCash);
    }

    public function test_destroy_returns_422_for_closed_daily_cash(): void
    {
        $dailyCash = DailyCash::factory()->closed()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/daily-cashes/{$dailyCash->uuid}")
            ->assertUnprocessable();
    }

    public function test_destroy_requires_auth(): void
    {
        $dailyCash = DailyCash::factory()->create();

        $this->deleteJson("/api/daily-cashes/{$dailyCash->uuid}")->assertUnauthorized();
    }
}
