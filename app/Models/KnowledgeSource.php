<?php

declare(strict_types=1);

namespace App\Models;

final class KnowledgeSource
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $botId,
        public readonly string $type,
        public readonly string $sourceName,
        public readonly ?string $sourceUrl,
        public readonly ?string $filePath,
        public readonly ?string $rawText,
        public readonly string $status,
        public readonly ?int $characterCount,
        public readonly int $chunkCount,
        public readonly ?string $errorMessage,
        public readonly ?string $processedAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            uuid: (string) $row['uuid'],
            botId: (int) $row['bot_id'],
            type: (string) $row['type'],
            sourceName: (string) $row['source_name'],
            sourceUrl: $row['source_url'] ?? null,
            filePath: $row['file_path'] ?? null,
            rawText: $row['raw_text'] ?? null,
            status: (string) $row['status'],
            characterCount: isset($row['character_count']) ? (int) $row['character_count'] : null,
            chunkCount: (int) ($row['chunk_count'] ?? 0),
            errorMessage: $row['error_message'] ?? null,
            processedAt: $row['processed_at'] ?? null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'type' => $this->type,
            'source_name' => $this->sourceName,
            'source_url' => $this->sourceUrl,
            'status' => $this->status,
            'character_count' => $this->characterCount,
            'chunk_count' => $this->chunkCount,
            'error_message' => $this->errorMessage,
            'processed_at' => $this->processedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
