<?php

declare(strict_types=1);

return [
    'mailer' => env('MAIL_MAILER', 'smtp'),

    'host' => env('MAIL_HOST', ''),

    'port' => (int) env('MAIL_PORT', '587'),

    'encryption' => env('MAIL_ENCRYPTION', 'tls'),

    'username' => env('MAIL_USERNAME', ''),

    'password' => env('MAIL_PASSWORD', ''),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@localhost'),
        'name' => env('MAIL_FROM_NAME', 'AI Chatbot SaaS'),
    ],
];
