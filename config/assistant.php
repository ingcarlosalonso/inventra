<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Assistant model
    |--------------------------------------------------------------------------
    |
    | Groq periodically retires "preview" models. GROQ_MODEL can be swapped
    | via env without a code deploy. GROQ_FALLBACK_MODELS is a comma-separated
    | list AssistantService tries, in order, if the primary model fails —
    | `assistant:check-model` (scheduled daily) warns when the primary model
    | stops working so it can be updated.
    |
    */

    'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),

    'fallback_models' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('GROQ_FALLBACK_MODELS', 'openai/gpt-oss-20b'))
    ))),
];
