<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Core\Contracts\VectorSearchRepositoryInterface;
use App\Core\Logging\LoggerFactory;
use App\DTO\AI\EmbeddingRequest;
use App\Repositories\DocumentRepository;
use Throwable;

/**
 * Generates and stores embeddings for knowledge-base chunks
 * (`documents` rows). Provider-agnostic: which provider/model is used
 * comes from config, resolved via AIProviderFactory — swapping the
 * embedding provider never touches this class.
 */
final class EmbeddingService
{
    /**
     * Gemini's batchEmbedContents has a practical request-size limit;
     * chunking client-side keeps individual HTTP calls small and
     * retry-friendly regardless of how many total chunks a knowledge
     * source produced.
     */
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly AIProviderFactory $providers,
        private readonly VectorSearchRepositoryInterface $vectorSearch,
        private readonly DocumentRepository $documents,
    ) {
    }

    /**
     * Embeds every chunk belonging to a knowledge source that doesn't
     * already have a vector. Failures for one batch don't abort the
     * rest — they're logged and the knowledge source ends up with a
     * partially-searchable set of chunks rather than none at all.
     *
     * @return array{embedded: int, failed: int}
     */
    public function embedForKnowledgeSource(int $knowledgeSourceId, string $providerName, string $model): array
    {
        $rows = $this->documents->forKnowledgeSource($knowledgeSourceId);

        return $this->embedRows($rows, $providerName, $model);
    }

    /**
     * Re-embeds every chunk for a bot — used when an admin changes the
     * bot's AI provider/embedding model and existing vectors are no
     * longer comparable to new queries.
     *
     * @return array{embedded: int, failed: int}
     */
    public function reembedForBot(int $botId, string $providerName, string $model): array
    {
        $this->vectorSearch->deleteForBot($botId);
        $rows = $this->documents->forBot($botId);

        return $this->embedRows($rows, $providerName, $model);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{embedded: int, failed: int}
     */
    private function embedRows(array $rows, string $providerName, string $model): array
    {
        if (empty($rows)) {
            return ['embedded' => 0, 'failed' => 0];
        }

        $provider = $this->providers->embeddingProvider($providerName);
        $embedded = 0;
        $failed = 0;

        foreach (array_chunk($rows, self::BATCH_SIZE) as $batch) {
            $texts = array_map(fn (array $row) => (string) $row['content'], $batch);

            try {
                $response = $provider->embed(new EmbeddingRequest($texts, $model));

                foreach ($batch as $i => $row) {
                    if (!isset($response->vectors[$i])) {
                        $failed++;

                        continue;
                    }

                    $this->vectorSearch->upsert((int) $row['bot_id'], (int) $row['id'], $response->vectors[$i], $response->model);
                    $embedded++;
                }
            } catch (Throwable $e) {
                $failed += count($batch);

                LoggerFactory::channel('system')->error('Embedding batch failed.', [
                    'provider' => $providerName,
                    'model' => $model,
                    'batch_size' => count($batch),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['embedded' => $embedded, 'failed' => $failed];
    }
}
