<?php

declare(strict_types=1);

namespace App\Repositories;

final class WebhookLogRepository extends BaseRepository
{
    protected string $table = 'webhook_logs';
    protected bool $usesSoftDeletes = false;

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForWebhook(int $webhookId, int $page, int $perPage): array
    {
        return $this->query()->where('webhook_id', '=', $webhookId)->orderBy('created_at', 'DESC')->paginate($page, $perPage);
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateAll(int $page, int $perPage): array
    {
        return $this->query()->orderBy('created_at', 'DESC')->paginate($page, $perPage);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingRetries(int $limit = 20): array
    {
        return $this->query()->where('status', '=', 'failed')->where('attempt', '<', 5)->orderBy('id', 'ASC')->limit($limit)->get();
    }

    public function markResult(int $id, string $status, ?int $responseStatus, ?string $responseBody): void
    {
        $this->update($id, [
            'status' => $status,
            'response_status' => $responseStatus,
            'response_body' => $responseBody !== null ? mb_substr($responseBody, 0, 5000) : null,
        ]);
    }

    public function incrementAttempt(int $id): void
    {
        \App\Core\Database\Connection::get()
            ->prepare('UPDATE webhook_logs SET attempt = attempt + 1 WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
