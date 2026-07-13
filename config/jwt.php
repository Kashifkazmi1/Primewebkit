<?php

declare(strict_types=1);

return [
    'secret' => env('JWT_SECRET', ''),

    'algo' => env('JWT_ALGO', 'HS256'),

    'issuer' => env('JWT_ISSUER', 'ai-chatbot-saas'),

    'audience' => env('JWT_AUDIENCE', 'ai-chatbot-saas-clients'),

    'access_ttl_minutes' => (int) env('JWT_ACCESS_TTL_MINUTES', '15'),

    'refresh_ttl_days' => (int) env('JWT_REFRESH_TTL_DAYS', '30'),
];
