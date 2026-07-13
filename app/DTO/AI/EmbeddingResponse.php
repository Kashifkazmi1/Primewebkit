<?php

declare(strict_types=1);

namespace App\DTO\AI;

final class EmbeddingResponse
{
    /**
     * @param list<list<float>> $vectors one vector per input text, same order
     */
    public function __construct(
        public readonly array $vectors,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?int $totalTokens,
        public readonly int $latencyMs,
    ) {
    }
}
