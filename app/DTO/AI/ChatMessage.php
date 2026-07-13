<?php

declare(strict_types=1);

namespace App\DTO\AI;

/**
 * A single message in a chat exchange, provider-agnostic.
 */
final class ChatMessage
{
    public function __construct(
        public readonly string $role, // 'user' | 'assistant' | 'system'
        public readonly string $content,
    ) {
    }

    /**
     * @return array{role: string, content: string}
     */
    public function toArray(): array
    {
        return ['role' => $this->role, 'content' => $this->content];
    }
}
