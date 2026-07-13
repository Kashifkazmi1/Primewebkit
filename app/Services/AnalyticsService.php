<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database\Connection;
use PDO;

/**
 * Read-only aggregation queries backing the analytics dashboards
 * (per-bot and platform-wide). All aggregation happens in SQL, not by
 * fetching rows into PHP, since usage tables can grow large.
 */
final class AnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function forBot(int $botId, string $groupBy = 'day', int $limit = 30): array
    {
        $dateFormat = match ($groupBy) {
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d',
        };

        $conversationSeries = $this->fetchSeries(
            "SELECT DATE_FORMAT(started_at, :fmt) AS bucket, COUNT(*) AS total
             FROM conversations WHERE bot_id = :bot_id
             GROUP BY bucket ORDER BY bucket DESC LIMIT :limit",
            ['fmt' => $dateFormat, 'bot_id' => $botId, 'limit' => $limit]
        );

        $messageSeries = $this->fetchSeries(
            "SELECT DATE_FORMAT(m.created_at, :fmt) AS bucket, COUNT(*) AS total
             FROM messages m JOIN conversations c ON c.id = m.conversation_id
             WHERE c.bot_id = :bot_id GROUP BY bucket ORDER BY bucket DESC LIMIT :limit",
            ['fmt' => $dateFormat, 'bot_id' => $botId, 'limit' => $limit]
        );

        $leadSeries = $this->fetchSeries(
            "SELECT DATE_FORMAT(created_at, :fmt) AS bucket, COUNT(*) AS total
             FROM leads WHERE bot_id = :bot_id GROUP BY bucket ORDER BY bucket DESC LIMIT :limit",
            ['fmt' => $dateFormat, 'bot_id' => $botId, 'limit' => $limit]
        );

        return [
            'conversations_by_period' => $conversationSeries,
            'messages_by_period' => $messageSeries,
            'leads_by_period' => $leadSeries,
            'averages' => $this->averagesForBot($botId),
            'most_asked_questions' => $this->mostAskedQuestions($botId),
            'lead_conversion_rate' => $this->leadConversionRate($botId),
            'average_rating' => $this->averageRating($botId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function averagesForBot(int $botId): array
    {
        $statement = Connection::get()->prepare(
            "SELECT
                AVG(response_duration_ms) AS avg_response_ms,
                AVG(total_tokens) AS avg_tokens,
                AVG(estimated_cost) AS avg_cost
             FROM ai_usage_logs WHERE bot_id = :bot_id AND status = 'success'"
        );
        $statement->execute(['bot_id' => $botId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'response_time_ms' => round((float) ($row['avg_response_ms'] ?? 0), 1),
            'tokens_per_message' => round((float) ($row['avg_tokens'] ?? 0), 1),
            'cost_per_message' => round((float) ($row['avg_cost'] ?? 0), 6),
        ];
    }

    /**
     * Groups user messages by a normalized (lowercased, whitespace-
     * collapsed, punctuation-stripped) form to approximate "most
     * frequently asked questions" without needing a dedicated
     * clustering/embedding-based approach.
     *
     * @return list<array{question: string, count: int}>
     */
    private function mostAskedQuestions(int $botId, int $limit = 10): array
    {
        $statement = Connection::get()->prepare(
            "SELECT m.content FROM messages m
             JOIN conversations c ON c.id = m.conversation_id
             WHERE c.bot_id = :bot_id AND m.role = 'user'
             ORDER BY m.id DESC LIMIT 2000"
        );
        $statement->execute(['bot_id' => $botId]);
        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);

        $counts = [];
        $display = [];

        foreach ($rows as $content) {
            $normalized = $this->normalizeQuestion((string) $content);

            if ($normalized === '') {
                continue;
            }

            $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;

            if (!isset($display[$normalized])) {
                $display[$normalized] = trim((string) $content);
            }
        }

        arsort($counts);

        $top = array_slice($counts, 0, $limit, true);

        return array_map(
            fn (string $key, int $count) => ['question' => $display[$key], 'count' => $count],
            array_keys($top),
            array_values($top)
        );
    }

    private function normalizeQuestion(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text) ?? $text;

        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }

    private function leadConversionRate(int $botId): float
    {
        $statement = Connection::get()->prepare('SELECT COUNT(*) AS total FROM conversations WHERE bot_id = :bot_id');
        $statement->execute(['bot_id' => $botId]);
        $conversations = (int) ($statement->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        if ($conversations === 0) {
            return 0.0;
        }

        $statement = Connection::get()->prepare('SELECT COUNT(*) AS total FROM leads WHERE bot_id = :bot_id');
        $statement->execute(['bot_id' => $botId]);
        $leads = (int) ($statement->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        return round(($leads / $conversations) * 100, 2);
    }

    private function averageRating(int $botId): ?float
    {
        $statement = Connection::get()->prepare(
            'SELECT AVG(rating) AS avg_rating, COUNT(rating) AS rated_count FROM conversations WHERE bot_id = :bot_id AND rating IS NOT NULL'
        );
        $statement->execute(['bot_id' => $botId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        if ((int) ($row['rated_count'] ?? 0) === 0) {
            return null;
        }

        return round((float) $row['avg_rating'], 2);
    }

    /**
     * @param array<string, mixed> $bindings
     * @return list<array{bucket: string, total: int}>
     */
    private function fetchSeries(string $sql, array $bindings): array
    {
        $statement = Connection::get()->prepare($sql);

        foreach ($bindings as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn (array $row) => ['bucket' => (string) $row['bucket'], 'total' => (int) $row['total']],
            $rows
        );
    }
}
