<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'AI Chatbot SaaS'),

    // local | development | staging | production
    'env' => env('APP_ENV', 'production'),

    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),

    'url' => rtrim(env('APP_URL', 'http://localhost'), '/'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'locale' => env('APP_LOCALE', 'en'),

    /**
     * 32+ character application key used for symmetric encryption
     * (e.g. AES-256-CBC) of sensitive values at rest.
     */
    'key' => env('APP_KEY', ''),

    /**
     * API versioning. All routes are prefixed with this segment,
     * e.g. /api/v1/...
     */
    'api_version' => 'v1',

    /**
     * List of environments considered "non-production" for the
     * purposes of verbose error output and debug logging.
     */
    'non_production_envs' => ['local', 'development', 'testing'],
];
