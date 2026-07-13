<?php

declare(strict_types=1);

namespace App\Repositories;

final class SubscriptionRepository extends BaseRepository
{
    protected string $table = 'subscriptions';
    protected bool $usesSoftDeletes = false;

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    public function findByUuidForUser(string $uuid, int $userId): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->where('user_id', '=', $userId)->first();
    }

    /**
     * The single active/trialing subscription for a user, if any.
     * Enforced as at-most-one by SubscriptionService (a new
     * subscription supersedes the previous one rather than stacking).
     */
    public function activeForUser(int $userId): ?array
    {
        return $this->query()
            ->where('user_id', '=', $userId)
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(int $userId): array
    {
        return $this->query()->where('user_id', '=', $userId)->orderBy('id', 'DESC')->get();
    }

    public function countActiveForPlan(int $planId): int
    {
        return $this->query()->where('plan_id', '=', $planId)->whereIn('status', ['active', 'trialing', 'past_due'])->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function expiringTrials(string $beforeDate): array
    {
        return $this->query()
            ->where('status', '=', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereRaw('trial_ends_at <= :before_date', ['before_date' => $beforeDate])
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function expiredGracePeriods(string $asOf): array
    {
        return $this->query()
            ->where('status', '=', 'past_due')
            ->whereNotNull('grace_period_ends_at')
            ->whereRaw('grace_period_ends_at <= :as_of', ['as_of' => $asOf])
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dueForRenewal(string $asOf): array
    {
        return $this->query()
            ->where('status', '=', 'active')
            ->where('cancel_at_period_end', '=', 0)
            ->whereRaw('current_period_end <= :as_of', ['as_of' => $asOf])
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function countsByStatus(): array
    {
        $rows = \App\Core\Database\QueryBuilder::table('subscriptions')
            ->withoutSoftDeletes()
            ->select(['status', 'COUNT(*) AS total'])
            ->groupBy('status')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}
