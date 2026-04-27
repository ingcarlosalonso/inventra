<?php

namespace Tests\Feature\Controllers;

use App\Models\ProductMovementType;
use Tests\Feature\TenantFeatureTestCase;

class ProductMovementTypeControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_paginated_list(): void
    {
        ProductMovementType::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/product-movement-types')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_filters_by_search(): void
    {
        ProductMovementType::factory()->create(['name' => 'Ingreso Manual']);
        ProductMovementType::factory()->create(['name' => 'Ajuste Pérdida']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/product-movement-types?search=Ingreso')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/product-movement-types')->assertUnauthorized();
    }

    public function test_store_creates_movement_type(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/product-movement-types', [
                'name' => 'Entrada Stock',
                'is_income' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Entrada Stock')
            ->assertJsonPath('data.is_income', true);

        $this->assertDatabaseHas('product_movement_types', ['name' => 'Entrada Stock'], 'tenant');
    }

    public function test_store_validates_name_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/product-movement-types', ['is_income' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_is_income_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/product-movement-types', ['name' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_income']);
    }

    public function test_store_validates_name_unique(): void
    {
        ProductMovementType::factory()->create(['name' => 'Duplicado']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/product-movement-types', ['name' => 'Duplicado', 'is_income' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_modifies_movement_type(): void
    {
        $type = ProductMovementType::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/product-movement-types/{$type->uuid}", [
                'name' => 'Nombre Actualizado',
                'is_income' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre Actualizado')
            ->assertJsonPath('data.is_income', false);
    }

    public function test_destroy_soft_deletes(): void
    {
        $type = ProductMovementType::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/product-movement-types/{$type->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('product_movement_types', ['id' => $type->id], 'tenant');
    }
}
