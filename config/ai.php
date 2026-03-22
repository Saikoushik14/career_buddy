<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Career AI Provider
    |--------------------------------------------------------------------------
    |
    | Supported:
    | - openai (OpenAI API or OpenAI-compatible endpoints)
    |
    */
    'provider' => env('AI_PROVIDER', 'openai'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'timeout_seconds' => (int) env('OPENAI_TIMEOUT_SECONDS', 20),
    ],

    /*
    | If true, the app will attempt to call the AI provider.
    | If the key is missing or the call fails, we fall back to heuristics.
    */
    'enabled' => env('AI_ENABLED', false),
];

