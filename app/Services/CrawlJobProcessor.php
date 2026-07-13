<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Support\TokenEstimator;
use App\Repositories\BotRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\KnowledgeSourceRepository;
use App\Repositories\WebsiteCrawlJobRepository;
use Throwable;

/**
 * Processes queued website_crawl_jobs rows: crawls the site, merges
 * page text, chunks it, and stores the result exactly like any other
 * knowledge source. Invoked by bin/process-crawl-jobs.php on a
 * schedule (Hostinger cron), never inline on an HTTP request.
 */
final class CrawlJobProcessor
{
    public function __construct(
        private readonly WebsiteCrawlJobRepository $crawlJobs,
        private readonly KnowledgeSourceRepository $knowledgeSources,
        private readonly DocumentRepository $documents,
        private readonly WebsiteCrawlerService $crawler,
        private readonly TextChunkerService $chunker,
        private readonly \App\Services\AI\EmbeddingService $embeddings,
        private readonly WebhookDispatcherService $webhooks,
        private readonly BotRepository $bots,
    ) {
    }

    /**
     * @return array{processed: int, failed: int}
     */
    public function processQueued(int $limit = 5): array
    {
        $jobs = $this->crawlJobs->queued($limit);
        $processed = 0;
        $failed = 0;

        foreach ($jobs as $job) {
            try {
                $this->processJob($job);
                $processed++;
            } catch (Throwable $e) {
                $this->crawlJobs->markFailed((int) $job['id'], $e->getMessage());
                $this->knowledgeSources->markFailed((int) $job['knowledge_source_id'], $e->getMessage());
                $failed++;
            }
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    /**
     * @param array<string, mixed> $job
     */
    private function processJob(array $job): void
    {
        $jobId = (int) $job['id'];
        $knowledgeSourceId = (int) $job['knowledge_source_id'];

        $this->crawlJobs->markCrawling($jobId);
        $this->knowledgeSources->markProcessing($knowledgeSourceId);

        $result = $this->crawler->crawl((string) $job['start_url'], (int) $job['max_pages']);

        if (empty($result['pages'])) {
            throw new \RuntimeException(
                'No pages could be crawled from this URL. It may be unreachable, blocking automated requests, or returning non-HTML content.'
            );
        }

        $combinedText = '';

        foreach ($result['pages'] as $url => $pageText) {
            $combinedText .= "### {$url}\n\n{$pageText}\n\n";
        }

        $knowledgeSource = $this->knowledgeSources->find($knowledgeSourceId);
        $botId = (int) $knowledgeSource['bot_id'];

        $chunks = $this->chunker->chunk($combinedText);

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

        $this->knowledgeSources->markCompleted($knowledgeSourceId, mb_strlen($combinedText), count($chunks));
        $this->crawlJobs->markCompleted($jobId, count($result['discovered']), count($result['pages']));

        if (!empty($chunks)) {
            $providerName = (string) config('ai.default_provider', 'gemini');
            $embeddingModel = (string) config('gemini.embedding_model');
            $this->embeddings->embedForKnowledgeSource($knowledgeSourceId, $providerName, $embeddingModel);
        }

        $bot = $this->bots->find($botId);

        if ($bot !== null) {
            $this->webhooks->dispatch('knowledge.uploaded', [
                'bot_id' => $bot['uuid'],
                'knowledge_source_id' => $knowledgeSourceId,
                'character_count' => mb_strlen($combinedText),
                'chunk_count' => count($chunks),
            ]);
        }
    }
}
