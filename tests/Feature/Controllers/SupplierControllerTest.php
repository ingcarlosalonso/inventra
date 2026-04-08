<?php

namespace Tests\Feature\Controllers;

use App\Models\Supplier;
use Tests\Feature\TenantFeatureTestCase;

class SupplierControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_paginated_list(): void
    {
        Supplier::factory()->count(5)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/suppliers')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_filters_by_search(): void
    {
        Supplier::factory()->create(['name' => 'Proveedor ABC', 'email' => 'abc@abc.com']);
        Supplier::factory()->create(['name' => 'Distribuidora XYZ', 'email' => 'xyz@xyz.com']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/suppliers?search=ABC')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/suppliers')->assertUnauthorized();
    }

    public function test_store_creates_supplier(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/suppliers', [
                'name' => 'ACME Corp',
                'email' => 'acme@corp.com',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'ACME Corp');

        $this->assertDatabaseHas('suppliers', ['name' => 'ACME Corp'], 'tenant');
    }

    public function test_store_validates_name_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/suppliers', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_email_format(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/suppliers', ['name' => 'Test', 'email' => 'not-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_modifies_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/suppliers/{$supplier->uuid}", ['name' => 'Nuevo Nombre', 'is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo Nombre');
    }

    public function test_destroy_soft_deletes(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/suppliers/{$supplier->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id], 'tenant');
    }
}
