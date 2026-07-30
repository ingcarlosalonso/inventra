<?php

namespace Tests\Unit\Adapters;

use App\Adapters\GroqAdapter;
use App\Exceptions\GroqUnavailableException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GroqAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'prism.providers.groq.api_key' => 'test-key',
            'prism.providers.groq.url' => 'https://api.groq.com/openai/v1',
        ]);
    }

    public function test_returns_model_ids_from_the_groq_models_endpoint(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/models' => Http::response([
                'data' => [
                    ['id' => 'llama-3.3-70b-versatile'],
                    ['id' => 'llama-3.1-8b-instant'],
                ],
            ]),
        ]);

        $models = (new GroqAdapter)->availableModels();

        $this->assertSame(['llama-3.3-70b-versatile', 'llama-3.1-8b-instant'], $models);
    }

    public function test_sends_the_configured_api_key_as_a_bearer_token(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/models' => Http::response(['data' => []]),
        ]);

        (new GroqAdapter)->availableModels();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key'));
    }

    public function test_throws_when_the_request_fails(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/models' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $this->expectException(GroqUnavailableException::class);

        (new GroqAdapter)->availableModels();
    }
}
