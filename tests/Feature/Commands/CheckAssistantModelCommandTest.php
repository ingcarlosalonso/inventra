<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CheckAssistantModelCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'prism.providers.groq.api_key' => 'test-key',
            'prism.providers.groq.url' => 'https://api.groq.com/openai/v1',
            'assistant.model' => 'llama-3.3-70b-versatile',
            'assistant.fallback_models' => ['llama-3.1-8b-instant'],
        ]);
    }

    public function test_succeeds_when_all_configured_models_are_available(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/models' => Http::response([
                'data' => [
                    ['id' => 'llama-3.3-70b-versatile'],
                    ['id' => 'llama-3.1-8b-instant'],
                ],
            ]),
        ]);

        $this->artisan('assistant:check-model')->assertExitCode(0);
    }

    public function test_fails_and_logs_when_the_primary_model_is_missing(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/models' => Http::response([
                'data' => [
                    ['id' => 'llama-3.1-8b-instant'],
                ],
            ]),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('assistant:check-model: some configured Groq models are no longer available', [
                'missing' => ['llama-3.3-70b-versatile'],
                'primary_model_missing' => true,
            ]);

        $this->artisan('assistant:check-model')->assertExitCode(1);
    }

    public function test_succeeds_when_only_a_fallback_model_is_missing(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/models' => Http::response([
                'data' => [
                    ['id' => 'llama-3.3-70b-versatile'],
                ],
            ]),
        ]);

        Log::shouldReceive('warning')->once();

        $this->artisan('assistant:check-model')->assertExitCode(0);
    }

    public function test_fails_and_logs_when_groq_is_unreachable(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/models' => Http::response(['error' => 'server error'], 500),
        ]);

        Log::shouldReceive('error')->once();

        $this->artisan('assistant:check-model')->assertExitCode(1);
    }
}
