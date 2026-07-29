<?php

namespace App\Console\Commands;

use App\Adapters\GroqAdapter;
use App\Exceptions\GroqUnavailableException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckAssistantModelCommand extends Command
{
    protected $signature = 'assistant:check-model';

    protected $description = 'Verify the Groq model(s) configured for the AI Assistant are still available';

    public function __construct(private readonly GroqAdapter $groq)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $configuredModels = array_values(array_unique(array_filter([
            config('assistant.model'),
            ...config('assistant.fallback_models', []),
        ])));

        try {
            $available = $this->groq->availableModels();
        } catch (GroqUnavailableException $e) {
            $this->error("Could not reach Groq to verify models: {$e->getMessage()}");
            Log::error('assistant:check-model: could not reach Groq', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $missing = array_values(array_diff($configuredModels, $available));

        if ($missing === []) {
            $this->info('All configured Groq models are available: '.implode(', ', $configuredModels));

            return self::SUCCESS;
        }

        $primaryMissing = in_array(config('assistant.model'), $missing, true);

        Log::warning('assistant:check-model: some configured Groq models are no longer available', [
            'missing' => $missing,
            'primary_model_missing' => $primaryMissing,
        ]);

        $this->warn('These configured Groq models are no longer available: '.implode(', ', $missing));

        if ($primaryMissing) {
            $this->warn('The primary model (GROQ_MODEL) is unavailable — the assistant is currently relying on a fallback model. Update GROQ_MODEL as soon as possible.');
        }

        return $primaryMissing ? self::FAILURE : self::SUCCESS;
    }
}
