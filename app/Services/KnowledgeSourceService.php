<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Core\Support\TokenEstimator;
use App\Models\KnowledgeSource;
use App\Repositories\DocumentRepository;
use App\Repositories\KnowledgeSourceRepository;
use App\Repositories\WebsiteCrawlJobRepository;
use Throwable;

/**
 * Orchestrates ingestion of every knowledge-source type into chunked
 * `documents` rows ready for retrieval (embeddings themselves are
 * generated in Phase 4 once the Gemini client exists — chunk storage
 * here is provider-agnostic).
 */
final class KnowledgeSourceService
{
    public function __construct(
        private readonly KnowledgeSourceRepository $knowledgeSources,
        private readonly DocumentRepository $documents,
        private readonly WebsiteCrawlJobRepository $crawlJobs,
        private readonly DocumentTextExtractor $extractor,
        private readonly TextChunkerService $chunker,
        private readonly \App\Services\AI\EmbeddingService $embeddings,
        private readonly UsageCounterService $usageCounters,
        private readonly \App\Repositories\BotRepository $botRepository,
        private readonly WebhookDispatcherService $webhooks,
    ) {
    }

    public function addTextSource(int $botId, string $sourceName, string $rawText): KnowledgeSource
    {
        $id = (int) $this->knowledgeSources->create([
            'uuid' => str_uuid4(),
            'bot_id' => $botId,
            'type' => 'text',
            'source_name' => $sourceName,
            'raw_text' => $rawText,
            'status' => 'pending',
        ]);

        $this->processTextContent($id, $botId, $rawText);

        return KnowledgeSource::fromArray($this->knowledgeSources->find($id));
    }

    public function addQaSource(int $botId, string $question, string $answer): KnowledgeSource
    {
        $combined = "Q: {$question}\nA: {$answer}";

        return $this->addTextSource($botId, mb_substr($question, 0, 100), $combined);
    }

    /**
     * @param array{tmp_path: string, mime_type: string, original_name: string} $file
     */
    public function addDocumentSource(int $botId, array $file): KnowledgeSource
    {
        $storedPath = $this->storeUploadedFile($botId, $file['tmp_path'], $file['original_name']);

        $id = (int) $this->knowledgeSources->create([
            'uuid' => str_uuid4(),
            'bot_id' => $botId,
            'type' => 'document',
            'source_name' => $file['original_name'],
            'file_path' => $storedPath,
            'status' => 'pending',
        ]);

        $this->knowledgeSources->markProcessing($id);

        try {
            $text = $this->extractor->extract($storedPath, $file['mime_type']);
            $this->processTextContent($id, $botId, $text, alreadyMarkedProcessing: true);
        } catch (Throwable $e) {
            $this->knowledgeSources->markFailed($id, $e->getMessage());
        }

        return KnowledgeSource::fromArray($this->knowledgeSources->find($id));
    }

    public function addWebsiteSource(int $botId, string $startUrl, int $maxPages = 20): KnowledgeSource
    {
        if (filter_var($startUrl, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException(['start_url' => ['The start URL is not a valid URL.']]);
        }

        \App\Core\Security\SsrfGuard::assertSafeUrl($startUrl, 'start_url');

        $sourceId = (int) $this->knowledgeSources->create([
            'uuid' => str_uuid4(),
            'bot_id' => $botId,
            'type' => 'website',
            'source_name' => $startUrl,
            'source_url' => $startUrl,
            'status' => 'pending',
        ]);

        // The actual crawl runs out-of-band via bin/process-crawl-jobs.php
        // (triggered by a Hostinger cron job) — HTTP request timeouts on
        // shared hosting are too short to crawl multiple pages inline.
        $this->crawlJobs->create([
            'uuid' => str_uuid4(),
            'knowledge_source_id' => $sourceId,
            'start_url' => $startUrl,
            'status' => 'queued',
            'max_pages' => max(1, min(100, $maxPages)),
        ]);

        return KnowledgeSource::fromArray($this->knowledgeSources->find($sourceId));
    }

    /**
     * @return list<KnowledgeSource>
     */
    public function forBot(int $botId): array
    {
        return array_map(KnowledgeSource::fromArray(...), $this->knowledgeSources->forBot($botId));
    }

    public function getForBot(string $uuid, int $botId): KnowledgeSource
    {
        $row = $this->knowledgeSources->findByUuidForBot($uuid, $botId);

        if ($row === null) {
            throw new NotFoundException('Knowledge source not found.');
        }

        return KnowledgeSource::fromArray($row);
    }

    public function delete(string $uuid, int $botId): void
    {
        $source = $this->getForBot($uuid, $botId);
        $this->documents->deleteForKnowledgeSource($source->id);
        $this->knowledgeSources->delete($source->id);
    }

    /**
     * Chunks raw text and persists it as `documents` rows. Shared by
     * the text, Q&A, and document ingestion paths. Website ingestion
     * uses the same chunking logic from within the crawl-job processor.
     */
    public function processTextContent(int $knowledgeSourceId, int $botId, string $text, bool $alreadyMarkedProcessing = false): void
    {
        if (!$alreadyMarkedProcessing) {
            $this->knowledgeSources->markProcessing($knowledgeSourceId);
        }

        try {
            $chunks = $this->chunker->chunk($text);

            foreach ($chunks as $index => $chunkText) {
                $this->documents->create([
                    'uuid' => str_uuid4(),
                    'bot_id' => $botId,
                    'knowledge_source_id' => $knowledgeSourceId,
                    'chunk_index' => $index,
                    'content' => $chunkText,
                    'token_count' => TokenEstimator::estimate($chunkText),
                ]);
            }

            $this->knowledgeSources->markCompleted($knowledgeSourceId, mb_strlen($text), count($chunks));

            $bot = $this->botRepository->find($botId);
            if ($bot !== null) {
                $this->usageCounters->addKnowledgeMb((int) $bot['user_id'], mb_strlen($text) / (1024 * 1024));
            }

            if (!empty($chunks)) {
                $providerName = (string) config('ai.default_provider', 'gemini');
                $embeddingModel = (string) config('gemini.embedding_model');
                $this->embeddings->embedForKnowledgeSource($knowledgeSourceId, $providerName, $embeddingModel);
            }

            if ($bot !== null) {
                $this->webhooks->dispatch('knowledge.uploaded', [
                    'bot_id' => $bot['uuid'],
                    'knowledge_source_id' => $knowledgeSourceId,
                    'character_count' => mb_strlen($text),
                    'chunk_count' => count($chunks),
                ]);
            }
        } catch (Throwable $e) {
            $this->knowledgeSources->markFailed($knowledgeSourceId, $e->getMessage());
        }
    }

    private function storeUploadedFile(int $botId, string $tmpPath, string $originalName): string
    {
        $directory = storage_path("KnowledgeBase/{$botId}");

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName) ?? 'upload.bin';
        $destination = $directory . '/' . str_uuid4() . '_' . $safeName;

        if (!copy($tmpPath, $destination)) {
            throw new \RuntimeException('Failed to store the uploaded file.');
        }

        $bot = $this->botRepository->find($botId);
        if ($bot !== null) {
            $sizeBytes = filesize($destination) ?: 0;
            $this->usageCounters->addStorageMb((int) $bot['user_id'], $sizeBytes / (1024 * 1024));
        }

        return $destination;
    }
}
