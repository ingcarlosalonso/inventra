<?php

namespace Tests\Feature\Controllers;

use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Tests\Feature\TenantFeatureTestCase;

class AssistantControllerTest extends TenantFeatureTestCase
{
    public function test_chat_requires_auth(): void
    {
        $this->postJson('/api/v1/assistant/chat', [
            'messages' => [['role' => 'user', 'content' => 'Hola']],
        ])->assertUnauthorized();
    }

    public function test_chat_returns_assistant_reply(): void
    {
        Prism::fake([
            TextResponseFake::make()->withText('¡Hola! ¿En qué puedo ayudarte?'),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/assistant/chat', [
                'messages' => [['role' => 'user', 'content' => 'Hola']],
            ])
            ->assertOk()
            ->assertJson(['message' => '¡Hola! ¿En qué puedo ayudarte?']);
    }

    public function test_chat_requires_messages(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/assistant/chat', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['messages']);
    }

    public function test_chat_rejects_invalid_role(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/assistant/chat', [
                'messages' => [['role' => 'system', 'content' => 'Hola']],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['messages.0.role']);
    }

    public function test_chat_rejects_content_over_max_length(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/assistant/chat', [
                'messages' => [['role' => 'user', 'content' => str_repeat('a', 2001)]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['messages.0.content']);
    }

    public function test_chat_rejects_more_than_50_messages(): void
    {
        $messages = array_fill(0, 51, ['role' => 'user', 'content' => 'Hola']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/assistant/chat', ['messages' => $messages])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['messages']);
    }
}
