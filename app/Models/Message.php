<?php

declare(strict_types=1);

namespace App\Models;

final class Message
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $conversationId,
        public readonly string $role,
        public readonly string $content,
        public readonly ?int $tokensUsed,
        public readonly ?int $latencyMs,
        public readonly string $createdAt,
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
            conversationId: (int) $row['conversation_id'],
            role: (string) $row['role'],
            content: (string) $row['content'],
            tokensUsed: isset($row['tokens_used']) ? (int) $row['tokens_used'] : null,
            latencyMs: isset($row['latency_ms']) ? (int) $row['latency_ms'] : null,
            createdAt: (string) $row['created_at'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'role' => $this->role,
            'content' => $this->content,
            'created_at' => $this->createdAt,
        ];
    }
}
