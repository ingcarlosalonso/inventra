<?php

namespace Tests\Feature\Controllers;

use App\Models\Customization;
use Tests\Feature\TenantFeatureTestCase;

class CustomizationControllerTest extends TenantFeatureTestCase
{
    public function test_show_returns_customization(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customization')
            ->assertOk()
            ->assertJsonStructure(['data' => ['logo_url', 'primary_color', 'secondary_color', 'accent_color', 'font_family']]);
    }

    public function test_show_creates_record_if_missing(): void
    {
        Customization::query()->delete();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customization')
            ->assertOk()
            ->assertJsonPath('data.primary_color', '#3B82F6');

        $this->assertDatabaseCount('customizations', 1, 'tenant');
    }

    public function test_show_requires_auth(): void
    {
        $this->getJson('/api/customization')->assertUnauthorized();
    }

    public function test_update_saves_colors_and_font(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customization', [
                'primary_color' => '#FF0000',
                'secondary_color' => '#00FF00',
                'accent_color' => '#0000FF',
                'font_family' => 'Montserrat',
            ])
            ->assertOk()
            ->assertJsonPath('data.primary_color', '#FF0000')
            ->assertJsonPath('data.font_family', 'Montserrat');

        $this->assertDatabaseHas('customizations', ['primary_color' => '#FF0000', 'font_family' => 'Montserrat'], 'tenant');
    }

    public function test_update_rejects_invalid_color(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customization', ['primary_color' => 'notacolor'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['primary_color']);
    }

    public function test_update_rejects_invalid_font(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customization', ['font_family' => 'Comic Sans'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['font_family']);
    }

    public function test_update_requires_auth(): void
    {
        $this->postJson('/api/customization', [])->assertUnauthorized();
    }
}
