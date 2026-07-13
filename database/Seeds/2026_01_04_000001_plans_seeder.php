<?php

declare(strict_types=1);

use App\Core\Database\Seeder;
use App\Repositories\PlanRepository;

return new class extends Seeder {
    public function run(): void
    {
        $plans = new PlanRepository();

        $definitions = [
            [
                'name' => 'Free', 'slug' => 'free', 'description' => 'Get started with a single AI chatbot at no cost.',
                'monthly_price' => 0, 'yearly_price' => 0, 'currency' => 'USD',
                'bots_limit' => 1, 'messages_limit' => 100, 'knowledge_limit_mb' => 5, 'storage_limit_mb' => 20, 'team_members_limit' => 1,
                'api_access' => 0, 'analytics' => 0, 'white_label' => 0, 'custom_domain' => 0, 'priority_support' => 0, 'streaming' => 1,
                'trial_days' => 0, 'is_active' => 1, 'sort_order' => 1,
            ],
            [
                'name' => 'Starter', 'slug' => 'starter', 'description' => 'For small businesses launching their first AI-powered support bot.',
                'monthly_price' => 19, 'yearly_price' => 180, 'currency' => 'USD',
                'bots_limit' => 3, 'messages_limit' => 2000, 'knowledge_limit_mb' => 50, 'storage_limit_mb' => 200, 'team_members_limit' => 3,
                'api_access' => 1, 'analytics' => 1, 'white_label' => 0, 'custom_domain' => 0, 'priority_support' => 0, 'streaming' => 1,
                'trial_days' => 14, 'is_active' => 1, 'sort_order' => 2,
            ],
            [
                'name' => 'Pro', 'slug' => 'pro', 'description' => 'For growing teams that need more bots, more capacity, and white-label branding.',
                'monthly_price' => 59, 'yearly_price' => 564, 'currency' => 'USD',
                'bots_limit' => 10, 'messages_limit' => 10000, 'knowledge_limit_mb' => 250, 'storage_limit_mb' => 1000, 'team_members_limit' => 10,
                'api_access' => 1, 'analytics' => 1, 'white_label' => 1, 'custom_domain' => 1, 'priority_support' => 1, 'streaming' => 1,
                'trial_days' => 14, 'is_active' => 1, 'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise', 'slug' => 'enterprise', 'description' => 'Unlimited scale, dedicated support, and full white-label control.',
                'monthly_price' => 199, 'yearly_price' => 1908, 'currency' => 'USD',
                'bots_limit' => -1, 'messages_limit' => -1, 'knowledge_limit_mb' => -1, 'storage_limit_mb' => -1, 'team_members_limit' => -1,
                'api_access' => 1, 'analytics' => 1, 'white_label' => 1, 'custom_domain' => 1, 'priority_support' => 1, 'streaming' => 1,
                'trial_days' => 30, 'is_active' => 1, 'sort_order' => 4,
            ],
        ];

        foreach ($definitions as $definition) {
            if ($plans->findBySlug($definition['slug']) !== null) {
                continue;
            }

            $definition['uuid'] = str_uuid4();
            $plans->create($definition);
        }
    }
};
