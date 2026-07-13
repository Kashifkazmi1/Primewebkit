<?php

declare(strict_types=1);

return [
    'api_key' => env('GEMINI_API_KEY', ''),

    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),

    'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'),

    'embedding_dimensions' => (int) env('GEMINI_EMBEDDING_DIMENSIONS', '768'),

    'timeout_seconds' => (int) env('GEMINI_TIMEOUT_SECONDS', '30'),

    'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', '2048'),
];
