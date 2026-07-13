<?php

declare(strict_types=1);

/**
 * Fallback limits applied to a user with no active subscription
 * (e.g. immediately after registration, before choosing a plan, or
 * after a subscription has fully expired). Mirrors the seeded "Free"
 * plan's limits — kept here too so limit checks never fail open if
 * the Free plan row is ever deleted/renamed.
 */
return [
    'bots_limit' => 1,
    'messages_limit' => 100,
    'knowledge_limit_mb' => 5,
    'storage_limit_mb' => 20,
    'team_members_limit' => 1,
    'api_access' => false,
    'analytics' => false,
    'white_label' => false,
    'custom_domain' => false,
    'priority_support' => false,
    'streaming' => true,
];
