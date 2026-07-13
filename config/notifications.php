<?php

declare(strict_types=1);

return [
    /**
     * Notification types that also send an email, in addition to the
     * always-created in-app notification. Kept short deliberately —
     * most events (e.g. routine renewal reminders) are fine as in-app
     * only; billing and access-affecting events warrant an email.
     */
    'email_types' => [
        'subscription.created',
        'subscription.trial_ended',
        'subscription.expired',
        'payment.failed',
        'team.invitation',
        'usage.limit_exceeded',
    ],
];
