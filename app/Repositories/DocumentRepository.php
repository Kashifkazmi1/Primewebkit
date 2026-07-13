<?php

declare(strict_types=1);

namespace App\Repositories;

final class DocumentRepository extends BaseRepository
{
    protected string $table = 'documents';
    protected bool $usesSoftDeletes = false;

    /**
     * Fetches specific chunks by id, joined with their knowledge
     * source's display name — used by RagPipelineService to attach a
     * human-readable source label to retrieved context.
     *
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public function findByIdsWithSource(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return \App\Core\Database\QueryBuilder::table('documents')
            ->withoutSoftDeletes()
            ->select(['documents.*', 'knowledge_sources.source_name'])
            ->join('knowledge_sources', 'knowledge_sources.id', '=', 'documents.knowledge_source_id')
            ->whereIn('documents.id', $ids)
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forBot(int $botId): array
    {
        return $this->query()->where('bot_id', '=', $botId)->orderBy('id', 'ASC')->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forKnowledgeSource(int $knowledgeSourceId): array
    {
        return $this->query()->where('knowledge_source_id', '=', $knowledgeSourceId)->orderBy('chunk_index', 'ASC')->get();
    }

    public function deleteForKnowledgeSource(int $knowledgeSourceId): int
    {
        return $this->query()->where('knowledge_source_id', '=', $knowledgeSourceId)->delete();
    }

    public function countForBot(int $botId): int
    {
        return $this->query()->where('bot_id', '=', $botId)->count();
    }
}
