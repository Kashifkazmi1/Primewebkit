<?php

declare(strict_types=1);

namespace App\DTO\AI;

/**
 * A single incremental piece of a streamed chat response, passed to
 * the caller's onChunk callback as it arrives from the provider.
 */
final class StreamChunk
{
    public function __construct(
        public readonly string $delta,
        public readonly bool $isFinal = false,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
        public readonly ?int $totalTokens = null,
        public readonly ?string $finishReason = null,
    ) {
    }
}
