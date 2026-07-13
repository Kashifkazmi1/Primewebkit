<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\DTO\AI\EmbeddingRequest;
use App\DTO\AI\EmbeddingResponse;

/**
 * Contract for providers capable of generating text embeddings.
 * A provider may implement this, AIChatProviderInterface, both, or
 * neither combination is enforced — EmbeddingService and
 * ChatOrchestratorService each depend only on the interface they need.
 */
interface AIEmbeddingProviderInterface
{
    public function embed(EmbeddingRequest $request): EmbeddingResponse;

    public function providerName(): string;

    /**
     * The fixed dimensionality of vectors this provider's configured
     * model produces (e.g. 768) — needed by the vector search layer
     * to validate stored vectors are comparable.
     */
    public function embeddingDimensions(): int;
}
