<?php

declare(strict_types=1);

namespace App\Models;

final class Bot
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $userId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $avatarPath,
        public readonly string $status,
        public readonly string $aiProvider,
        public readonly string $model,
        public readonly ?string $systemPrompt,
        public readonly float $temperature,
        public readonly int $maxOutputTokens,
        public readonly ?string $welcomeMessage,
        public readonly ?string $primaryColor,
        public readonly bool $isPublic,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly float $topP = 0.95,
        public readonly int $topK = 40,
        public readonly array $safetySettings = [],
        public readonly string $language = 'en',
        public readonly ?string $personality = null,
        public readonly ?string $tone = null,
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
            userId: (int) $row['user_id'],
            name: (string) $row['name'],
            description: $row['description'] ?? null,
            avatarPath: $row['avatar_path'] ?? null,
            status: (string) $row['status'],
            aiProvider: (string) $row['ai_provider'],
            model: (string) $row['model'],
            systemPrompt: $row['system_prompt'] ?? null,
            temperature: (float) $row['temperature'],
            maxOutputTokens: (int) $row['max_output_tokens'],
            welcomeMessage: $row['welcome_message'] ?? null,
            primaryColor: $row['primary_color'] ?? null,
            isPublic: (bool) $row['is_public'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            topP: (float) ($row['top_p'] ?? 0.95),
            topK: (int) ($row['top_k'] ?? 40),
            safetySettings: !empty($row['safety_settings']) ? (json_decode((string) $row['safety_settings'], true) ?: []) : [],
            language: (string) ($row['language'] ?? 'en'),
            personality: $row['personality'] ?? null,
            tone: $row['tone'] ?? null,
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'avatar_url' => $this->avatarPath,
            'status' => $this->status,
            'ai_provider' => $this->aiProvider,
            'model' => $this->model,
            'system_prompt' => $this->systemPrompt,
            'temperature' => $this->temperature,
            'max_output_tokens' => $this->maxOutputTokens,
            'top_p' => $this->topP,
            'top_k' => $this->topK,
            'safety_settings' => $this->safetySettings,
            'language' => $this->language,
            'personality' => $this->personality,
            'tone' => $this->tone,
            'welcome_message' => $this->welcomeMessage,
            'primary_color' => $this->primaryColor,
            'is_public' => $this->isPublic,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Shape exposed to the public widget embed script — never
     * includes the system prompt or internal model configuration.
     *
     * @return array<string, mixed>
     */
    public function toWidgetArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'avatar_url' => $this->avatarPath,
            'welcome_message' => $this->welcomeMessage,
            'primary_color' => $this->primaryColor,
        ];
    }
}
