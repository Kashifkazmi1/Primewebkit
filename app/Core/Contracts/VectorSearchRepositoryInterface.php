<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Contract for a vector similarity search backend. The MySQL
 * implementation (computing cosine similarity in PHP over rows
 * scoped by bot_id) is provided now, appropriate for the knowledge-base
 * sizes a single chatbot typically has on shared hosting. A dedicated
 * vector database (Qdrant, Pinecone, Weaviate, Supabase Vector) can be
 * added later purely by writing a new class implementing this
 * interface and swapping the container binding in bootstrap/bindings.php
 * — RagPipelineService and EmbeddingService never reference a
 * concrete backend.
 */
interface VectorSearchRepositoryInterface
{
    /**
     * Store or update the vector for a single document chunk.
     *
     * @param list<float> $vector
     */
    public function upsert(int $botId, int $documentId, array $vector, string $model): void;

    /**
     * @param list<float> $queryVector
     * @return list<array{document_id: int, score: float}> Ordered by score descending.
     */
    public function search(int $botId, array $queryVector, int $topK = 5, float $minScore = 0.0): array;

    public function delete(int $documentId): void;

    public function deleteForBot(int $botId): void;

    public function hasVector(int $documentId): bool;
}
