<?php

declare(strict_types=1);

return [
    'force_https' => filter_var(env('SECURITY_FORCE_HTTPS', 'true'), FILTER_VALIDATE_BOOLEAN),

    'hsts' => [
        'enabled' => filter_var(env('SECURITY_HSTS_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', '31536000'),
    ],

    /**
     * General API rate limiting (per IP + route bucket).
     */
    'rate_limit' => [
        'enabled' => filter_var(env('SECURITY_RATE_LIMIT_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
        'max_attempts' => (int) env('SECURITY_RATE_LIMIT_MAX_ATTEMPTS', '60'),
        'decay_seconds' => (int) env('SECURITY_RATE_LIMIT_DECAY_SECONDS', '60'),
    ],

    /**
     * Stricter throttling + account lock specifically for the login endpoint.
     */
    'login_throttle' => [
        'max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', '5'),
        'lockout_minutes' => (int) env('SECURITY_LOGIN_LOCKOUT_MINUTES', '15'),
    ],

    /**
     * Secure headers applied to every response.
     */
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'",
    ],
];
