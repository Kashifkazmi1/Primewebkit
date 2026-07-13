<?php

declare(strict_types=1);

namespace App\DTO\AI;

/**
 * A provider-agnostic chat completion result, including usage/timing
 * metadata needed for AI usage tracking regardless of which provider
 * produced it.
 */
final class ChatResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?int $promptTokens,
        public readonly ?int $completionTokens,
        public readonly ?int $totalTokens,
        public readonly string $finishReason,
        public readonly int $latencyMs,
        public readonly bool $wasBlocked = false,
        public readonly ?string $blockReason = null,
    ) {
    }
}
