<?php

namespace Tests\Feature\Controllers;

use App\Models\Presentation;
use App\Models\PresentationType;
use Tests\Feature\TenantFeatureTestCase;

class PresentationControllerTest extends TenantFeatureTestCase
{
    // ── index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_list(): void
    {
        $before = Presentation::count();
        Presentation::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/presentations')
            ->assertOk()
            ->assertJsonCount($before + 3, 'data');
    }

    public function test_index_filters_by_search(): void
    {
        $type = PresentationType::factory()->create(['name' => '__TIPO_KG__', 'abbreviation' => '__tkg__']);
        Presentation::factory()->create(['presentation_type_id' => $type->id, 'quantity' => 1]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/presentations?search=__TIPO_KG__')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/presentations')->assertUnauthorized();
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_presentation(): void
    {
        $type = PresentationType::factory()->create(['abbreviation' => 'kg']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/presentations', [
                'presentation_type_id' => $type->uuid,
                'quantity' => 1,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.quantity', 1)
            ->assertJsonPath('data.display', '1 kg');

        $this->assertDatabaseHas('presentations', ['quantity' => 1.0], 'tenant');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/presentations', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presentation_type_id', 'quantity']);
    }

    public function test_store_validates_presentation_type_exists(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/presentations', [
                'presentation_type_id' => 'uuid-inexistente',
                'quantity' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presentation_type_id']);
    }

    public function test_store_validates_quantity_positive(): void
    {
        $type = PresentationType::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/presentations', [
                'presentation_type_id' => $type->uuid,
                'quantity' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_update_modifies_presentation(): void
    {
        $presentation = Presentation::factory()->create(['quantity' => 1]);
        $type = $presentation->presentationType;

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/presentations/{$presentation->uuid}", [
                'presentation_type_id' => $type->uuid,
                'quantity' => 500,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity', 500);
    }

    public function test_update_requires_auth(): void
    {
        $presentation = Presentation::factory()->create();
        $type = $presentation->presentationType;

        $this->putJson("/api/presentations/{$presentation->uuid}", [
            'presentation_type_id' => $type->uuid,
            'quantity' => 1,
        ])->assertUnauthorized();
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes(): void
    {
        $presentation = Presentation::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/presentations/{$presentation->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('presentations', ['id' => $presentation->id], 'tenant');
    }

    // ── toggle ────────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $presentation = Presentation::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/presentations/{$presentation->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
