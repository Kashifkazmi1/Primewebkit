<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\ConversationRepository;
use App\Repositories\MessageRepository;

final class ConversationService
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly MessageRepository $messages,
    ) {
    }

    public function startOrResume(int $botId, string $sessionId, ?int $visitorId): Conversation
    {
        $existing = $this->conversations->findBySessionId($botId, $sessionId);

        if ($existing !== null) {
            return Conversation::fromArray($existing);
        }

        $id = (int) $this->conversations->create([
            'uuid' => str_uuid4(),
            'bot_id' => $botId,
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'status' => 'active',
            'started_at' => now_utc()->format('Y-m-d H:i:s'),
            'last_message_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);

        return Conversation::fromArray($this->conversations->find($id));
    }

    public function appendMessage(int $conversationId, string $role, string $content, ?int $tokensUsed = null, ?int $latencyMs = null): Message
    {
        $id = (int) $this->messages->create([
            'uuid' => str_uuid4(),
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'tokens_used' => $tokensUsed,
            'latency_ms' => $latencyMs,
        ]);

        $this->conversations->incrementMessageCount($conversationId);

        $row = \App\Core\Database\QueryBuilder::table('messages')->withoutSoftDeletes()->find($id);

        return Message::fromArray($row);
    }

    /**
     * @return list<Message>
     */
    public function history(int $conversationId, int $limit = 100): array
    {
        return array_map(Message::fromArray(...), $this->messages->forConversation($conversationId, $limit));
    }

    /**
     * @return list<array{role: string, content: string}> compact form for AI context windows
     */
    public function recentContext(int $conversationId, int $limit = 20): array
    {
        $rows = $this->messages->recentForConversation($conversationId, $limit);

        return array_map(
            fn (array $row) => ['role' => $row['role'], 'content' => $row['content']],
            $rows
        );
    }

    public function getForBot(string $uuid, int $botId): Conversation
    {
        $row = $this->conversations->findByUuid($uuid);

        if ($row === null || (int) $row['bot_id'] !== $botId) {
            throw new NotFoundException('Conversation not found.');
        }

        return Conversation::fromArray($row);
    }

    /**
     * @return array{data: list<Conversation>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForBot(int $botId, int $page, int $perPage): array
    {
        $result = $this->conversations->paginateForBot($botId, $page, $perPage);
        $result['data'] = array_map(Conversation::fromArray(...), $result['data']);

        return $result;
    }

    public function close(string $uuid, int $botId): void
    {
        $conversation = $this->getForBot($uuid, $botId);
        $this->conversations->close($conversation->id);
    }

    public function requestCancellation(int $conversationId): void
    {
        $this->conversations->requestCancellation($conversationId);
    }

    public function rate(string $uuid, int $botId, int $rating, ?string $comment): void
    {
        $conversation = $this->getForBot($uuid, $botId);

        if ($rating < 1 || $rating > 5) {
            throw new \App\Exceptions\ValidationException(['rating' => ['Rating must be between 1 and 5.']]);
        }

        $this->conversations->rate($conversation->id, $rating, $comment);
    }
}
