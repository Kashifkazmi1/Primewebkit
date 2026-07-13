<?php

declare(strict_types=1);

return [
    /**
     * Default channel used by the generic app logger.
     */
    'default' => env('LOG_CHANNEL', 'daily'),

    'level' => env('LOG_LEVEL', 'info'),

    'retention_days' => (int) env('LOG_DAYS', '14'),

    /**
     * Named channels. Each maps to its own rotating file so that
     * application, API, authentication, activity and system events
     * can be audited independently.
     */
    'channels' => [
        'app' => [
            'path' => base_path(env('LOG_APP_FILE', 'storage/Logs/app.log')),
        ],
        'api' => [
            'path' => base_path(env('LOG_API_FILE', 'storage/Logs/api.log')),
        ],
        'auth' => [
            'path' => base_path(env('LOG_AUTH_FILE', 'storage/Logs/auth.log')),
        ],
        'activity' => [
            'path' => base_path(env('LOG_ACTIVITY_FILE', 'storage/Logs/activity.log')),
        ],
        'system' => [
            'path' => base_path(env('LOG_SYSTEM_FILE', 'storage/Logs/system.log')),
        ],
        'ai' => [
            'path' => base_path(env('LOG_AI_FILE', 'storage/Logs/ai.log')),
        ],
    ],
];
