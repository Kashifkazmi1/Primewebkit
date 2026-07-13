<?php

declare(strict_types=1);

namespace App\Models;

final class Conversation
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $botId,
        public readonly ?int $visitorId,
        public readonly string $sessionId,
        public readonly string $status,
        public readonly ?string $title,
        public readonly int $messageCount,
        public readonly string $startedAt,
        public readonly ?string $lastMessageAt,
        public readonly ?string $endedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            uuid: (string) $row['uuid'],
            botId: (int) $row['bot_id'],
            visitorId: isset($row['visitor_id']) ? (int) $row['visitor_id'] : null,
            sessionId: (string) $row['session_id'],
            status: (string) $row['status'],
            title: $row['title'] ?? null,
            messageCount: (int) ($row['message_count'] ?? 0),
            startedAt: (string) $row['started_at'],
            lastMessageAt: $row['last_message_at'] ?? null,
            endedAt: $row['ended_at'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'status' => $this->status,
            'title' => $this->title,
            'message_count' => $this->messageCount,
            'started_at' => $this->startedAt,
            'last_message_at' => $this->lastMessageAt,
            'ended_at' => $this->endedAt,
        ];
    }
}
