<?php

namespace Tests\Feature\Controllers;

use App\Models\PaymentMethod;
use Tests\Feature\TenantFeatureTestCase;

class PaymentMethodControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_list(): void
    {
        PaymentMethod::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/payment-methods')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/payment-methods')->assertUnauthorized();
    }

    public function test_index_filters_by_search(): void
    {
        $match = PaymentMethod::factory()->create(['name' => 'Efectivo Test']);
        PaymentMethod::factory()->create(['name' => 'Tarjeta Test']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/payment-methods?search=Efectivo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $match->name);
    }

    public function test_store_creates_payment_method(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/payment-methods', [
                'name' => 'Transferencia Test',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Transferencia Test');

        $this->assertDatabaseHas('payment_methods', ['name' => 'Transferencia Test'], 'tenant');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/payment-methods', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/payment-methods', ['name' => 'Test'])
            ->assertUnauthorized();
    }

    public function test_update_modifies_payment_method(): void
    {
        $method = PaymentMethod::factory()->create(['name' => 'Original Test']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/payment-methods/{$method->uuid}", [
                'name' => 'Actualizado Test',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Actualizado Test')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_update_requires_auth(): void
    {
        $method = PaymentMethod::factory()->create();

        $this->putJson("/api/payment-methods/{$method->uuid}", ['name' => 'X'])
            ->assertUnauthorized();
    }

    public function test_update_returns_404_for_missing(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/payment-methods/non-existent-uuid', ['name' => 'X'])
            ->assertNotFound();
    }

    public function test_destroy_soft_deletes(): void
    {
        $method = PaymentMethod::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/payment-methods/{$method->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('payment_methods', ['id' => $method->id], 'tenant');
    }

    public function test_toggle_flips_is_active(): void
    {
        $method = PaymentMethod::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/payment-methods/{$method->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_toggle_activates_inactive(): void
    {
        $method = PaymentMethod::factory()->inactive()->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/payment-methods/{$method->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    }
}
