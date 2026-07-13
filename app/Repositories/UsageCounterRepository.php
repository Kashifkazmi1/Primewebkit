<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database\Connection;
use App\Core\Database\QueryBuilder;

/**
 * All writes here use `INSERT ... ON DUPLICATE KEY UPDATE`, which
 * MySQL executes atomically — the read-check-then-write pattern this
 * originally used (SELECT to check existence, then either INSERT or
 * UPDATE) had a genuine race condition under concurrent requests for
 * the same user+metric+period: two simultaneous knowledge-base
 * uploads could both read "no row yet", both attempt an INSERT, and
 * the second would fail on the unique constraint; or two concurrent
 * increments could both read the same value and one write would be
 * silently lost. The atomic upsert form has neither failure mode.
 */
final class UsageCounterRepository extends BaseRepository
{
    protected string $table = 'usage_counters';
    protected bool $usesSoftDeletes = false;

    public function get(int $userId, string $metric, string $period): int
    {
        $row = $this->query()
            ->where('user_id', '=', $userId)
            ->where('metric', '=', $metric)
            ->where('period', '=', $period)
            ->first();

        return $row !== null ? (int) $row['value'] : 0;
    }

    public function increment(int $userId, string $metric, string $period, int $by = 1): void
    {
        $now = now_utc()->format('Y-m-d H:i:s');

        $statement = Connection::get()->prepare(
            'INSERT INTO usage_counters (user_id, metric, period, value, created_at, updated_at)
             VALUES (:user_id, :metric, :period, :value, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE value = value + :by, updated_at = :updated_at2'
        );

        $statement->execute([
            'user_id' => $userId,
            'metric' => $metric,
            'period' => $period,
            'value' => max(0, $by),
            'by' => $by,
            'created_at' => $now,
            'updated_at' => $now,
            'updated_at2' => $now,
        ]);
    }

    /**
     * Atomically adds $by to the counter, clamping the *result* at a
     * minimum of 0 (used for "subtract" operations where the counter
     * must never go negative, e.g. knowledge storage shrinking after
     * a delete). Still race-free: the clamping happens in the same
     * atomic statement via GREATEST(), not as a separate read+write.
     */
    public function incrementClamped(int $userId, string $metric, string $period, int $by): void
    {
        $now = now_utc()->format('Y-m-d H:i:s');

        $statement = Connection::get()->prepare(
            'INSERT INTO usage_counters (user_id, metric, period, value, created_at, updated_at)
             VALUES (:user_id, :metric, :period, :value, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE value = GREATEST(0, value + :by), updated_at = :updated_at2'
        );

        $statement->execute([
            'user_id' => $userId,
            'metric' => $metric,
            'period' => $period,
            'value' => max(0, $by),
            'by' => $by,
            'created_at' => $now,
            'updated_at' => $now,
            'updated_at2' => $now,
        ]);
    }

    public function set(int $userId, string $metric, string $period, int $value): void
    {
        $now = now_utc()->format('Y-m-d H:i:s');

        $statement = Connection::get()->prepare(
            'INSERT INTO usage_counters (user_id, metric, period, value, created_at, updated_at)
             VALUES (:user_id, :metric, :period, :value, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE value = :value2, updated_at = :updated_at2'
        );

        $statement->execute([
            'user_id' => $userId,
            'metric' => $metric,
            'period' => $period,
            'value' => $value,
            'value2' => $value,
            'created_at' => $now,
            'updated_at' => $now,
            'updated_at2' => $now,
        ]);
    }

    /**
     * @return array<string, int> metric => total value across all users for the given period
     */
    public function totalsForPeriod(string $period): array
    {
        $rows = QueryBuilder::table('usage_counters')
            ->withoutSoftDeletes()
            ->select(['metric', 'SUM(value) AS total'])
            ->where('period', '=', $period)
            ->groupBy('metric')
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $totals[$row['metric']] = (int) $row['total'];
        }

        return $totals;
    }
}
