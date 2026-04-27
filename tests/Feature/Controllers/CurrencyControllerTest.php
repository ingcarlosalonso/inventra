<?php

namespace Tests\Feature\Controllers;

use App\Models\Currency;
use Tests\Feature\TenantFeatureTestCase;

class CurrencyControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_list(): void
    {
        Currency::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/currencies')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/currencies')->assertUnauthorized();
    }

    public function test_index_filters_by_search(): void
    {
        $match = Currency::factory()->create(['name' => 'Peso Especial Test', 'iso_code' => 'XPS']);
        Currency::factory()->create(['name' => 'Dólar Test', 'iso_code' => 'XDT']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/currencies?search=Especial')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $match->name);
    }

    public function test_store_creates_currency(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/currencies', [
                'name' => 'Guaraní Test',
                'symbol' => '₲',
                'iso_code' => 'XGT',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Guaraní Test');

        $this->assertDatabaseHas('currencies', ['name' => 'Guaraní Test'], 'tenant');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/currencies', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'symbol', 'iso_code']);
    }

    public function test_store_sets_default_unsets_previous(): void
    {
        $existing = Currency::factory()->create(['is_default' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/currencies', [
                'name' => 'Dólar Test Default',
                'symbol' => 'T$',
                'iso_code' => 'XDD',
                'is_default' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('currencies', ['id' => $existing->id, 'is_default' => false], 'tenant');
    }

    public function test_update_modifies_currency(): void
    {
        $currency = Currency::factory()->create(['name' => 'Moneda Test', 'symbol' => 'MT', 'iso_code' => 'XMT']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/currencies/{$currency->uuid}", [
                'name' => 'Moneda Test Actualizada',
                'symbol' => 'MT',
                'iso_code' => 'XMT',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Moneda Test Actualizada')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_destroy_soft_deletes(): void
    {
        $currency = Currency::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/currencies/{$currency->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('currencies', ['id' => $currency->id], 'tenant');
    }

    public function test_toggle_flips_is_active(): void
    {
        $currency = Currency::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/currencies/{$currency->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
