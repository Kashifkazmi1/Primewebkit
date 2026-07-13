<?php

declare(strict_types=1);

return [
    /**
     * Which PaymentProviderInterface implementation is active.
     * 'manual' requires no external gateway and is fully functional
     * out of the box. Set to 'stripe' once a StripePaymentProvider
     * class exists and Stripe credentials are configured below.
     */
    'default_provider' => env('BILLING_PROVIDER', 'manual'),

    'currency' => env('BILLING_DEFAULT_CURRENCY', 'USD'),

    /**
     * Days a past_due subscription keeps working before being marked
     * expired — gives a customer time to fix a failed payment.
     */
    'grace_period_days' => (int) env('BILLING_GRACE_PERIOD_DAYS', '7'),

    /**
     * Stripe credentials are read here so a future StripePaymentProvider
     * has a stable config location, but are unused while
     * default_provider=manual.
     */
    'stripe' => [
        'key' => env('STRIPE_KEY', ''),
        'secret' => env('STRIPE_SECRET', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],
];
