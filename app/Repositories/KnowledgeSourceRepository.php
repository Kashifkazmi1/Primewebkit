<?php

declare(strict_types=1);

namespace App\Repositories;

final class KnowledgeSourceRepository extends BaseRepository
{
    protected string $table = 'knowledge_sources';

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    public function findByUuidForBot(string $uuid, int $botId): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->where('bot_id', '=', $botId)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forBot(int $botId): array
    {
        return $this->query()->where('bot_id', '=', $botId)->orderBy('created_at', 'DESC')->get();
    }

    public function markProcessing(int $id): void
    {
        $this->update($id, ['status' => 'processing', 'error_message' => null]);
    }

    public function markCompleted(int $id, int $characterCount, int $chunkCount): void
    {
        $this->update($id, [
            'status' => 'completed',
            'character_count' => $characterCount,
            'chunk_count' => $chunkCount,
            'processed_at' => now_utc()->format('Y-m-d H:i:s'),
            'error_message' => null,
        ]);
    }

    public function markFailed(int $id, string $errorMessage): void
    {
        $this->update($id, ['status' => 'failed', 'error_message' => mb_substr($errorMessage, 0, 2000)]);
    }
}
