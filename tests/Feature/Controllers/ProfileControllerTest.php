<?php

namespace Tests\Feature\Controllers;

use Tests\Feature\TenantFeatureTestCase;

class ProfileControllerTest extends TenantFeatureTestCase
{
    public function test_update_password_requires_auth(): void
    {
        $this->putJson('/api/v1/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertUnauthorized();
    }

    public function test_update_password_succeeds(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'password',
                'password' => 'newpassword1',
                'password_confirmation' => 'newpassword1',
            ])
            ->assertOk()
            ->assertJsonStructure(['message']);
    }

    public function test_update_password_fails_with_wrong_current_password(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword1',
                'password_confirmation' => 'newpassword1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_update_password_fails_when_confirmation_does_not_match(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'password',
                'password' => 'newpassword1',
                'password_confirmation' => 'differentpassword',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_update_password_fails_when_password_too_short(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
