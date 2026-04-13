<?php

namespace Tests\Feature\Controllers;

use App\Models\Presentation;
use App\Models\PresentationType;
use Tests\Feature\TenantFeatureTestCase;

class PresentationTypeControllerTest extends TenantFeatureTestCase
{
    // ── index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_list(): void
    {
        $before = PresentationType::count();
        PresentationType::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/presentation-types')
            ->assertOk()
            ->assertJsonCount($before + 3, 'data');
    }

    public function test_index_filters_by_search(): void
    {
        PresentationType::factory()->create(['name' => '__TIPO_VOLUMEN__', 'abbreviation' => '__tv__']);
        PresentationType::factory()->create(['name' => '__TIPO_PESO__', 'abbreviation' => '__tp__']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/presentation-types?search=__TIPO_VOLUMEN__')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '__TIPO_VOLUMEN__');
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/presentation-types')->assertUnauthorized();
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_presentation_type(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/presentation-types', ['name' => '__STORE_TIPO__', 'abbreviation' => '__st__', 'is_active' => true])
            ->assertCreated()
            ->assertJsonPath('data.name', '__STORE_TIPO__')
            ->assertJsonPath('data.abbreviation', '__st__')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('presentation_types', ['name' => '__STORE_TIPO__'], 'tenant');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/presentation-types', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'abbreviation']);
    }

    public function test_store_validates_unique_name(): void
    {
        PresentationType::factory()->create(['name' => '__UNIQUE_NAME__', 'abbreviation' => '__un1__']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/presentation-types', ['name' => '__UNIQUE_NAME__', 'abbreviation' => '__un2__'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_update_modifies_presentation_type(): void
    {
        $type = PresentationType::factory()->create(['name' => '__UPDATE_ORIG__', 'abbreviation' => '__uo__']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/presentation-types/{$type->uuid}", ['name' => '__UPDATE_MOD__', 'abbreviation' => '__um__', 'is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.name', '__UPDATE_MOD__')
            ->assertJsonPath('data.abbreviation', '__um__');
    }

    public function test_update_requires_auth(): void
    {
        $type = PresentationType::factory()->create();

        $this->putJson("/api/presentation-types/{$type->uuid}", ['name' => 'X', 'abbreviation' => 'x'])->assertUnauthorized();
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes(): void
    {
        $type = PresentationType::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/presentation-types/{$type->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('presentation_types', ['id' => $type->id], 'tenant');
    }

    public function test_destroy_rejects_type_with_presentations(): void
    {
        $type = PresentationType::factory()->create();
        Presentation::factory()->create(['presentation_type_id' => $type->id]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/presentation-types/{$type->uuid}")
            ->assertStatus(422);
    }

    // ── toggle ────────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $type = PresentationType::factory()->create(['is_active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/presentation-types/{$type->uuid}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
