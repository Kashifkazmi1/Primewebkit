<?php

declare(strict_types=1);

namespace App\Services\AI\VectorSearch;

use App\Core\Contracts\VectorSearchRepositoryInterface;
use App\Core\Database\Connection;
use PDO;

/**
 * MySQL-backed vector search: embeddings are stored as JSON-encoded
 * float arrays in `documents.embedding`, and cosine similarity is
 * computed in PHP over the rows belonging to one bot.
 *
 * This is appropriate for the knowledge-base sizes a single chatbot
 * typically has (hundreds to a few thousand chunks) on shared
 * hosting with no vector-database add-on available. It intentionally
 * does NOT attempt to scale to millions of vectors — see
 * VectorSearchRepositoryInterface for how a dedicated vector database
 * (Qdrant, Pinecone, Weaviate, Supabase Vector) can be swapped in
 * later without touching RagPipelineService or EmbeddingService.
 */
final class MySqlVectorSearchRepository implements VectorSearchRepositoryInterface
{
    public function upsert(int $botId, int $documentId, array $vector, string $model): void
    {
        $statement = Connection::get()->prepare(
            'UPDATE documents SET embedding = :embedding, embedding_model = :model WHERE id = :id AND bot_id = :bot_id'
        );

        $statement->execute([
            'embedding' => json_encode($vector),
            'model' => $model,
            'id' => $documentId,
            'bot_id' => $botId,
        ]);
    }

    public function search(int $botId, array $queryVector, int $topK = 5, float $minScore = 0.0): array
    {
        $statement = Connection::get()->prepare(
            'SELECT id, embedding FROM documents WHERE bot_id = :bot_id AND embedding IS NOT NULL'
        );
        $statement->execute(['bot_id' => $botId]);

        $scored = [];

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $vector = json_decode((string) $row['embedding'], true);

            if (!is_array($vector) || empty($vector)) {
                continue;
            }

            $score = $this->cosineSimilarity($queryVector, $vector);

            if ($score >= $minScore) {
                $scored[] = ['document_id' => (int) $row['id'], 'score' => $score];
            }
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $topK);
    }

    public function delete(int $documentId): void
    {
        $statement = Connection::get()->prepare('UPDATE documents SET embedding = NULL, embedding_model = NULL WHERE id = :id');
        $statement->execute(['id' => $documentId]);
    }

    public function deleteForBot(int $botId): void
    {
        $statement = Connection::get()->prepare('UPDATE documents SET embedding = NULL, embedding_model = NULL WHERE bot_id = :bot_id');
        $statement->execute(['bot_id' => $botId]);
    }

    public function hasVector(int $documentId): bool
    {
        $statement = Connection::get()->prepare('SELECT embedding IS NOT NULL AS has_vector FROM documents WHERE id = :id');
        $statement->execute(['id' => $documentId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row !== false && (bool) $row['has_vector'];
    }

    /**
     * @param list<float> $a
     * @param list<float> $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
