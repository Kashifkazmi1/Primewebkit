<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Repositories\BotRepository;
use App\Repositories\PlanRepository;
use App\Repositories\TeamMemberRepository;
use App\Repositories\UsageCounterRepository;

/**
 * Resolves the limits that currently apply to a user (from their
 * active subscription's plan, or the platform's default free-tier
 * limits if they have none) and how much of each they've used.
 */
final class PlanLimitService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly PlanRepository $plans,
        private readonly UsageCounterRepository $usageCounters,
        private readonly BotRepository $bots,
        private readonly TeamMemberRepository $teamMembers,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function limitsFor(int $userId): array
    {
        $subscription = $this->subscriptions->currentForUser($userId);

        if ($subscription === null || !$subscription->isActive()) {
            return (array) config('default_plan_limits');
        }

        $planRow = $this->plans->find($subscription->planId);

        if ($planRow === null) {
            return (array) config('default_plan_limits');
        }

        $plan = Plan::fromArray($planRow);

        return [
            'bots_limit' => $plan->botsLimit,
            'messages_limit' => $plan->messagesLimit,
            'knowledge_limit_mb' => $plan->knowledgeLimitMb,
            'storage_limit_mb' => $plan->storageLimitMb,
            'team_members_limit' => $plan->teamMembersLimit,
            'api_access' => $plan->apiAccess,
            'analytics' => $plan->analytics,
            'white_label' => $plan->whiteLabel,
            'custom_domain' => $plan->customDomain,
            'priority_support' => $plan->prioritySupport,
            'streaming' => $plan->streaming,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function usageFor(int $userId): array
    {
        $period = now_utc()->format('Y-m');

        return [
            'bots' => $this->bots->countForUser($userId),
            'messages' => $this->usageCounters->get($userId, 'messages', $period),
            'knowledge_mb' => $this->usageCounters->get($userId, 'knowledge_mb', 'lifetime'),
            'storage_mb' => $this->usageCounters->get($userId, 'storage_mb', 'lifetime'),
            'team_members' => $this->countTeamMembersOwnedBy($userId),
            'api_requests' => $this->usageCounters->get($userId, 'api_requests', $period),
            'ai_requests' => $this->usageCounters->get($userId, 'ai_requests', $period),
        ];
    }

    /**
     * @return array{limits: array<string, mixed>, usage: array<string, mixed>}
     */
    public function limitsAndUsageFor(int $userId): array
    {
        return ['limits' => $this->limitsFor($userId), 'usage' => $this->usageFor($userId)];
    }

    public function hasReachedLimit(int $userId, string $metric): bool
    {
        $limits = $this->limitsFor($userId);
        $usage = $this->usageFor($userId);

        $limitKey = match ($metric) {
            'bots' => 'bots_limit',
            'messages' => 'messages_limit',
            'knowledge_mb' => 'knowledge_limit_mb',
            'storage_mb' => 'storage_limit_mb',
            'team_members' => 'team_members_limit',
            default => null,
        };

        if ($limitKey === null || !isset($limits[$limitKey])) {
            return false;
        }

        $limit = (int) $limits[$limitKey];

        // A limit of -1 conventionally means "unlimited" for that metric.
        if ($limit < 0) {
            return false;
        }

        return ($usage[$metric] ?? 0) >= $limit;
    }

    public function hasFeature(int $userId, string $feature): bool
    {
        $limits = $this->limitsFor($userId);

        return (bool) ($limits[$feature] ?? false);
    }

    private function countTeamMembersOwnedBy(int $userId): int
    {
        return $this->teamMembers->countMembersAcrossOwnedTeams($userId);
    }
}
