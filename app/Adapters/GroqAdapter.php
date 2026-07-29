<?php

namespace App\Adapters;

use App\Exceptions\GroqUnavailableException;
use Illuminate\Support\Facades\Http;

class GroqAdapter
{
    /**
     * @return array<int, string> model ids currently available to the configured API key
     */
    public function availableModels(): array
    {
        $response = Http::withToken((string) config('prism.providers.groq.api_key'))
            ->get(rtrim((string) config('prism.providers.groq.url'), '/').'/models');

        if ($response->failed()) {
            throw GroqUnavailableException::requestFailed($response->status());
        }

        return collect($response->json('data', []))->pluck('id')->all();
    }
}
