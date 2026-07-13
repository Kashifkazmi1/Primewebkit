<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Database\Connection;
use App\Repositories\CronJobRunRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\SubscriptionRepository;
use PDO;
use Throwable;

/**
 * Backs the Super Admin global dashboard. Every figure is computed
 * with a single SQL aggregate query rather than loading rows into
 * PHP — this endpoint is expected to be hit frequently (every admin
 * dashboard page load) and must stay fast regardless of table sizes.
 */
final class AdminDashboardService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions,
        private readonly InvoiceRepository $invoices,
        private readonly CronJobRunRepository $cronJobRuns,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'users' => $this->userStats(),
            'bots' => $this->botStats(),
            'conversations' => $this->conversationStats(),
            'ai' => $this->aiStats(),
            'storage' => $this->storageStats(),
            'revenue' => $this->revenueStats(),
            'subscriptions' => $this->subscriptions->countsByStatus(),
            'pending_payments' => $this->pendingPaymentsCount(),
            'webhooks' => $this->webhookStats(),
            'system_health' => $this->systemHealth(),
            'cron_jobs' => $this->cronJobStatus(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userStats(): array
    {
        $pdo = Connection::get();

        $total = (int) $pdo->query('SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL')->fetch(PDO::FETCH_ASSOC)['c'];
        $active = (int) $pdo->query("SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND status = 'active'")->fetch(PDO::FETCH_ASSOC)['c'];

        $today = now_utc()->format('Y-m-d');
        $statement = $pdo->prepare('SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND DATE(created_at) = :today');
        $statement->execute(['today' => $today]);
        $newToday = (int) $statement->fetch(PDO::FETCH_ASSOC)['c'];

        $monthStart = now_utc()->format('Y-m-01');
        $statement = $pdo->prepare('SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND created_at >= :month_start');
        $statement->execute(['month_start' => $monthStart]);
        $monthlySignups = (int) $statement->fetch(PDO::FETCH_ASSOC)['c'];

        return [
            'total' => $total,
            'active' => $active,
            'new_today' => $newToday,
            'monthly_signups' => $monthlySignups,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function botStats(): array
    {
        $pdo = Connection::get();

        $total = (int) $pdo->query('SELECT COUNT(*) AS c FROM bots WHERE deleted_at IS NULL')->fetch(PDO::FETCH_ASSOC)['c'];
        $active = (int) $pdo->query("SELECT COUNT(*) AS c FROM bots WHERE deleted_at IS NULL AND status = 'active'")->fetch(PDO::FETCH_ASSOC)['c'];

        return ['total' => $total, 'active' => $active];
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationStats(): array
    {
        $pdo = Connection::get();

        $totalConversations = (int) $pdo->query('SELECT COUNT(*) AS c FROM conversations')->fetch(PDO::FETCH_ASSOC)['c'];

        $today = now_utc()->format('Y-m-d');
        $statement = $pdo->prepare('SELECT COUNT(*) AS c FROM messages WHERE DATE(created_at) = :today');
        $statement->execute(['today' => $today]);
        $messagesToday = (int) $statement->fetch(PDO::FETCH_ASSOC)['c'];

        return ['total' => $totalConversations, 'messages_today' => $messagesToday];
    }

    /**
     * @return array<string, mixed>
     */
    private function aiStats(): array
    {
        $pdo = Connection::get();

        $row = $pdo->query(
            "SELECT COUNT(*) AS total_requests, COALESCE(SUM(total_tokens), 0) AS total_tokens, COALESCE(SUM(estimated_cost), 0) AS total_cost
             FROM ai_usage_logs WHERE status = 'success'"
        )->fetch(PDO::FETCH_ASSOC);

        $today = now_utc()->format('Y-m-d');
        $statement = $pdo->prepare("SELECT COUNT(*) AS c FROM ai_usage_logs WHERE DATE(created_at) = :today AND status = 'success'");
        $statement->execute(['today' => $today]);
        $requestsToday = (int) $statement->fetch(PDO::FETCH_ASSOC)['c'];

        return [
            'total_requests' => (int) $row['total_requests'],
            'requests_today' => $requestsToday,
            'total_tokens' => (int) $row['total_tokens'],
            'estimated_cost' => round((float) $row['total_cost'], 6),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storageStats(): array
    {
        $pdo = Connection::get();
        $knowledgeMb = (int) $pdo->query("SELECT COALESCE(SUM(value), 0) AS v FROM usage_counters WHERE metric = 'knowledge_mb'")->fetch(PDO::FETCH_ASSOC)['v'];
        $storageMb = (int) $pdo->query("SELECT COALESCE(SUM(value), 0) AS v FROM usage_counters WHERE metric = 'storage_mb'")->fetch(PDO::FETCH_ASSOC)['v'];

        $diskFreeBytes = @disk_free_space(base_path());
        $diskTotalBytes = @disk_total_space(base_path());

        return [
            'knowledge_mb' => $knowledgeMb,
            'uploads_mb' => $storageMb,
            'disk_free_mb' => $diskFreeBytes !== false ? (int) round($diskFreeBytes / (1024 * 1024)) : null,
            'disk_total_mb' => $diskTotalBytes !== false ? (int) round($diskTotalBytes / (1024 * 1024)) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function revenueStats(): array
    {
        $monthStart = now_utc()->format('Y-m-01 00:00:00');

        return [
            'total_paid' => $this->invoices->sumPaidRevenue(),
            'this_month' => $this->invoices->sumPaidRevenue($monthStart),
        ];
    }

    private function pendingPaymentsCount(): int
    {
        $statement = Connection::get()->query("SELECT COUNT(*) AS c FROM invoices WHERE status = 'open'");

        return (int) $statement->fetch(PDO::FETCH_ASSOC)['c'];
    }

    /**
     * @return array<string, mixed>
     */
    private function webhookStats(): array
    {
        $pdo = Connection::get();
        $row = $pdo->query(
            "SELECT
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS success_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count
             FROM webhook_logs"
        )->fetch(PDO::FETCH_ASSOC);

        return [
            'success' => (int) ($row['success_count'] ?? 0),
            'failed' => (int) ($row['failed_count'] ?? 0),
            'pending' => (int) ($row['pending_count'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function systemHealth(): array
    {
        $databaseOk = true;

        try {
            Connection::get()->query('SELECT 1');
        } catch (Throwable) {
            $databaseOk = false;
        }

        return [
            'database' => $databaseOk ? 'ok' : 'unavailable',
            'php_version' => PHP_VERSION,
            'server_time' => now_utc()->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cronJobStatus(): array
    {
        $latest = $this->cronJobRuns->latestPerJob();

        return array_map(
            fn (array $run) => [
                'job_name' => $run['job_name'],
                'status' => $run['status'],
                'started_at' => $run['started_at'],
                'finished_at' => $run['finished_at'],
            ],
            $latest
        );
    }
}
