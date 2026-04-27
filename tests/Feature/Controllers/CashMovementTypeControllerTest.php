<?php

namespace Tests\Feature\Controllers;

use App\Models\CashMovementType;
use Tests\Feature\TenantFeatureTestCase;

class CashMovementTypeControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_paginated_list(): void
    {
        CashMovementType::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/cash-movement-types')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_filters_by_search(): void
    {
        CashMovementType::factory()->create(['name' => 'Depósito Banco']);
        CashMovementType::factory()->create(['name' => 'Retiro Caja']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/cash-movement-types?search=Depósito')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/cash-movement-types')->assertUnauthorized();
    }

    public function test_store_creates_movement_type(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cash-movement-types', [
                'name' => 'Pago Proveedor',
                'is_income' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Pago Proveedor')
            ->assertJsonPath('data.is_income', false);

        $this->assertDatabaseHas('cash_movement_types', ['name' => 'Pago Proveedor'], 'tenant');
    }

    public function test_store_validates_name_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cash-movement-types', ['is_income' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_is_income_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cash-movement-types', ['name' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_income']);
    }

    public function test_store_validates_name_unique(): void
    {
        CashMovementType::factory()->create(['name' => 'Duplicado']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cash-movement-types', ['name' => 'Duplicado', 'is_income' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_modifies_movement_type(): void
    {
        $type = CashMovementType::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/cash-movement-types/{$type->uuid}", [
                'name' => 'Nombre Actualizado',
                'is_income' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre Actualizado');
    }

    public function test_destroy_soft_deletes(): void
    {
        $type = CashMovementType::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/cash-movement-types/{$type->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('cash_movement_types', ['id' => $type->id], 'tenant');
    }
}
