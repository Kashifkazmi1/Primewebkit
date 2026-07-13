<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Core\Contracts\VectorSearchRepositoryInterface;
use App\DTO\AI\EmbeddingRequest;
use App\Models\Bot;
use App\Repositories\DocumentRepository;
use App\Core\Logging\LoggerFactory;
use Throwable;

/**
 * The retrieval half of RAG: takes a raw user query, returns a
 * ranked, deduplicated, token-budgeted list of knowledge-base chunks
 * ready for PromptEngineService to inject as context. Generation
 * (calling the AI provider) is the caller's responsibility
 * (ChatOrchestratorService) — this class only retrieves.
 */
final class RagPipelineService
{
    public function __construct(
        private readonly AIProviderFactory $providers,
        private readonly VectorSearchRepositoryInterface $vectorSearch,
        private readonly DocumentRepository $documents,
    ) {
    }

    /**
     * @return list<array{content: string, score: float, source_name: string}>
     */
    public function retrieve(Bot $bot, string $userQuery): array
    {
        $normalizedQuery = $this->normalizeQuery($userQuery);

        if ($normalizedQuery === '') {
            return [];
        }

        $topK = (int) config('ai.rag.top_k', 5);
        $minScore = (float) config('ai.rag.min_score', 0.55);

        try {
            $embeddingModel = (string) config('gemini.embedding_model');
            $embeddingProvider = $this->providers->embeddingProvider($bot->aiProvider);
            $embeddingResponse = $embeddingProvider->embed(new EmbeddingRequest([$normalizedQuery], $embeddingModel));
            $queryVector = $embeddingResponse->vectors[0] ?? [];

            if (empty($queryVector)) {
                return [];
            }

            // Retrieve a slightly larger candidate set than needed so
            // ranking/deduplication has room to drop near-duplicates
            // without falling short of $topK.
            $matches = $this->vectorSearch->search($bot->id, $queryVector, $topK * 2, $minScore);
        } catch (Throwable $e) {
            // Retrieval failing (embedding API down, etc.) should
            // degrade to "no context found" rather than fail the
            // entire chat turn — the assistant can still respond using
            // only its system prompt and conversation memory.
            LoggerFactory::channel('system')->warning('RAG retrieval failed; continuing without knowledge context.', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        if (empty($matches)) {
            return [];
        }

        $documentIds = array_column($matches, 'document_id');
        $rows = $this->documents->findByIdsWithSource($documentIds);
        $rowsById = [];

        foreach ($rows as $row) {
            $rowsById[(int) $row['id']] = $row;
        }

        $candidates = [];

        foreach ($matches as $match) {
            $row = $rowsById[$match['document_id']] ?? null;

            if ($row === null) {
                continue;
            }

            $candidates[] = [
                'content' => (string) $row['content'],
                'score' => $match['score'],
                'source_name' => (string) $row['source_name'],
            ];
        }

        $ranked = $this->deduplicateAndRank($candidates);

        return array_slice($ranked, 0, $topK);
    }

    private function normalizeQuery(string $query): string
    {
        $query = trim($query);
        $query = preg_replace('/\s+/', ' ', $query) ?? $query;

        return $query;
    }

    /**
     * Removes near-duplicate chunks (common when a document was
     * chunked with overlap, or the same fact appears in multiple
     * sources) — keeps the highest-scoring instance of each.
     *
     * @param list<array{content: string, score: float, source_name: string}> $candidates
     * @return list<array{content: string, score: float, source_name: string}>
     */
    private function deduplicateAndRank(array $candidates): array
    {
        usort($candidates, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $kept = [];

        foreach ($candidates as $candidate) {
            $isDuplicate = false;

            foreach ($kept as $existing) {
                if ($this->similarityRatio($candidate['content'], $existing['content']) > 0.85) {
                    $isDuplicate = true;

                    break;
                }
            }

            if (!$isDuplicate) {
                $kept[] = $candidate;
            }
        }

        return $kept;
    }

    private function similarityRatio(string $a, string $b): float
    {
        similar_text($a, $b, $percent);

        return $percent / 100;
    }
}
