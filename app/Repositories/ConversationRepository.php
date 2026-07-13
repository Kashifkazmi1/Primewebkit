<?php

declare(strict_types=1);

namespace App\Repositories;

final class ConversationRepository extends BaseRepository
{
    protected string $table = 'conversations';
    protected bool $usesSoftDeletes = false;

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    public function findBySessionId(int $botId, string $sessionId): ?array
    {
        return $this->query()
            ->where('bot_id', '=', $botId)
            ->where('session_id', '=', $sessionId)
            ->where('status', '=', 'active')
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForBot(int $botId, int $page = 1, int $perPage = 20): array
    {
        return $this->query()->where('bot_id', '=', $botId)->orderBy('last_message_at', 'DESC')->paginate($page, $perPage);
    }

    public function incrementMessageCount(int $id): void
    {
        $this->update($id, [
            'last_message_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);

        \App\Core\Database\Connection::get()
            ->prepare('UPDATE conversations SET message_count = message_count + 1 WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function decrementMessageCount(int $id): void
    {
        \App\Core\Database\Connection::get()
            ->prepare('UPDATE conversations SET message_count = GREATEST(0, message_count - 1) WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function close(int $id): void
    {
        $this->update($id, ['status' => 'closed', 'ended_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function countForBot(int $botId): int
    {
        return $this->query()->where('bot_id', '=', $botId)->count();
    }

    /**
     * Marks a conversation as "currently generating" — set right
     * before an AI call starts, cleared right after it ends. Used so
     * a stop-generation request has something to flip, and so a
     * client can poll whether generation is still in progress.
     */
    public function markGenerating(int $id): void
    {
        $this->update($id, ['generating_since' => now_utc()->format('Y-m-d H:i:s'), 'cancel_requested_at' => null]);
    }

    public function clearGenerating(int $id): void
    {
        $this->update($id, ['generating_since' => null]);
    }

    public function requestCancellation(int $id): void
    {
        $this->update($id, ['cancel_requested_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function isCancellationRequested(int $id): bool
    {
        $row = $this->query()->select(['cancel_requested_at'])->where('id', '=', $id)->first();

        return $row !== null && $row['cancel_requested_at'] !== null;
    }

    public function isGenerating(int $id): bool
    {
        $row = $this->query()->select(['generating_since'])->where('id', '=', $id)->first();

        return $row !== null && $row['generating_since'] !== null;
    }

    public function rate(int $id, int $rating, ?string $comment): void
    {
        $this->update($id, ['rating' => $rating, 'rating_comment' => $comment]);
    }
}
