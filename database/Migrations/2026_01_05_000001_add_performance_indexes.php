<?php

declare(strict_types=1);

use App\Core\Database\Connection;
use App\Core\Database\Migration;

/**
 * Performance audit findings:
 *  - ai_usage_logs is frequently filtered by (bot_id, status) together
 *    (AnalyticsService::averagesForBot, AdminDashboardService::aiStats)
 *    but only had single-column indexes on each — a composite index
 *    lets MySQL satisfy both predicates from one index lookup instead
 *    of index-merge or a bot_id-only scan filtered further in memory.
 *  - messages.created_at is filtered directly by AdminDashboardService
 *    ("messages today") with no supporting index at all, meaning that
 *    query would degrade to a full table scan as message volume grows.
 */
return new class extends Migration {
    public function up(): void
    {
        $pdo = Connection::get();
        $pdo->exec('ALTER TABLE ai_usage_logs ADD INDEX idx_bot_status (bot_id, status)');
        $pdo->exec('ALTER TABLE messages ADD INDEX idx_created_at (created_at)');
    }

    public function down(): void
    {
        $pdo = Connection::get();
        $pdo->exec('ALTER TABLE ai_usage_logs DROP INDEX idx_bot_status');
        $pdo->exec('ALTER TABLE messages DROP INDEX idx_created_at');
    }
};
