<?php

declare(strict_types=1);

namespace App\Repositories;

final class MessageRepository extends BaseRepository
{
    protected string $table = 'messages';
    protected bool $usesSoftDeletes = false;

    /**
     * @return list<array<string, mixed>>
     */
    public function forConversation(int $conversationId, int $limit = 100): array
    {
        return $this->query()
            ->where('conversation_id', '=', $conversationId)
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get();
    }

    /**
     * Most recent N messages, oldest first — used to build AI context windows.
     *
     * @return list<array<string, mixed>>
     */
    public function recentForConversation(int $conversationId, int $limit = 20): array
    {
        $rows = $this->query()
            ->where('conversation_id', '=', $conversationId)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get();

        return array_reverse($rows);
    }

    public function countForBot(int $botId): int
    {
        return \App\Core\Database\QueryBuilder::table('messages')
            ->withoutSoftDeletes()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.bot_id', '=', $botId)
            ->count();
    }

    public function last(int $conversationId): ?array
    {
        return $this->query()
            ->where('conversation_id', '=', $conversationId)
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function deleteById(int $id): void
    {
        $this->query()->where('id', '=', $id)->delete();
    }
}
