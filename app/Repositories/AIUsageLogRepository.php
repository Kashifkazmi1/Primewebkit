<?php

declare(strict_types=1);

namespace App\Repositories;

final class AIUsageLogRepository extends BaseRepository
{
    protected string $table = 'ai_usage_logs';
    protected bool $usesSoftDeletes = false;

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForBot(int $botId, int $page = 1, int $perPage = 20): array
    {
        return $this->query()->where('bot_id', '=', $botId)->orderBy('created_at', 'DESC')->paginate($page, $perPage);
    }

    /**
     * @return array{total_requests: int, total_prompt_tokens: int, total_completion_tokens: int, total_tokens: int, total_cost: float, failed_requests: int}
     */
    public function summaryForBot(int $botId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $sql = 'SELECT
                COUNT(*) AS total_requests,
                COALESCE(SUM(prompt_tokens), 0) AS total_prompt_tokens,
                COALESCE(SUM(completion_tokens), 0) AS total_completion_tokens,
                COALESCE(SUM(total_tokens), 0) AS total_tokens,
                COALESCE(SUM(estimated_cost), 0) AS total_cost,
                SUM(CASE WHEN status != \'success\' THEN 1 ELSE 0 END) AS failed_requests
            FROM ai_usage_logs
            WHERE bot_id = :bot_id';

        $bindings = ['bot_id' => $botId];

        if ($fromDate !== null) {
            $sql .= ' AND created_at >= :from_date';
            $bindings['from_date'] = $fromDate;
        }

        if ($toDate !== null) {
            $sql .= ' AND created_at <= :to_date';
            $bindings['to_date'] = $toDate;
        }

        $statement = \App\Core\Database\Connection::get()->prepare($sql);
        $statement->execute($bindings);
        $row = $statement->fetch(\PDO::FETCH_ASSOC) ?: [];

        return [
            'total_requests' => (int) ($row['total_requests'] ?? 0),
            'total_prompt_tokens' => (int) ($row['total_prompt_tokens'] ?? 0),
            'total_completion_tokens' => (int) ($row['total_completion_tokens'] ?? 0),
            'total_tokens' => (int) ($row['total_tokens'] ?? 0),
            'total_cost' => round((float) ($row['total_cost'] ?? 0), 6),
            'failed_requests' => (int) ($row['failed_requests'] ?? 0),
        ];
    }
}
