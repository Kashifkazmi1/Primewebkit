<?php

declare(strict_types=1);

return [
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '')))),

    'allowed_methods' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')))),

    'allowed_headers' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With,X-API-Key')))),

    'allow_credentials' => filter_var(env('CORS_ALLOW_CREDENTIALS', 'true'), FILTER_VALIDATE_BOOLEAN),

    'max_age' => (int) env('CORS_MAX_AGE', '86400'),
];
