<?php

declare(strict_types=1);

namespace App\Repositories;

final class WebsiteCrawlJobRepository extends BaseRepository
{
    protected string $table = 'website_crawl_jobs';
    protected bool $usesSoftDeletes = false;

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function queued(int $limit = 5): array
    {
        return $this->query()
            ->where('status', '=', 'queued')
            ->orderBy('created_at', 'ASC')
            ->limit($limit)
            ->get();
    }

    public function markCrawling(int $id): void
    {
        $this->update($id, ['status' => 'crawling', 'started_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function markCompleted(int $id, int $pagesFound, int $pagesProcessed): void
    {
        $this->update($id, [
            'status' => 'completed',
            'pages_found' => $pagesFound,
            'pages_processed' => $pagesProcessed,
            'completed_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);
    }

    public function markFailed(int $id, string $errorMessage): void
    {
        $this->update($id, [
            'status' => 'failed',
            'error_message' => mb_substr($errorMessage, 0, 2000),
            'completed_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);
    }
}
