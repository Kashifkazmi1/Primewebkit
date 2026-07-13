<?php

declare(strict_types=1);

namespace App\DTO\AI;

/**
 * Batch embedding request — providers that don't natively support
 * batching should loop internally and still return one response.
 */
final class EmbeddingRequest
{
    /**
     * @param list<string> $texts
     */
    public function __construct(
        public readonly array $texts,
        public readonly string $model,
    ) {
    }
}
