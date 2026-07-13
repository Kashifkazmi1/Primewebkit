<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\Bot;
use App\Repositories\BotRepository;
use App\Repositories\WidgetRepository;

final class BotService
{
    public function __construct(
        private readonly BotRepository $bots,
        private readonly WidgetRepository $widgets,
        private readonly WebhookDispatcherService $webhooks,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $userId, array $data): Bot
    {
        $botId = (int) $this->bots->create([
            'uuid' => str_uuid4(),
            'user_id' => $userId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => 'draft',
            'ai_provider' => 'gemini',
            'model' => $data['model'] ?? (string) config('gemini.model', 'gemini-1.5-flash'),
            'system_prompt' => $data['system_prompt'] ?? $this->defaultSystemPrompt(),
            'temperature' => $data['temperature'] ?? 0.7,
            'max_output_tokens' => $data['max_output_tokens'] ?? (int) config('gemini.max_output_tokens', 2048),
            'top_p' => $data['top_p'] ?? 0.95,
            'top_k' => $data['top_k'] ?? 40,
            'language' => $data['language'] ?? 'en',
            'personality' => $data['personality'] ?? null,
            'tone' => $data['tone'] ?? null,
            'welcome_message' => $data['welcome_message'] ?? 'Hi! How can I help you today?',
            'primary_color' => $data['primary_color'] ?? '#4f46e5',
            'is_public' => 0,
        ]);

        // Every bot gets a default widget configuration automatically.
        $this->widgets->create([
            'uuid' => str_uuid4(),
            'bot_id' => $botId,
            'theme' => 'light',
            'position' => 'bottom-right',
            'primary_color' => $data['primary_color'] ?? '#4f46e5',
            'greeting_message' => $data['welcome_message'] ?? 'Hi! How can I help you today?',
            'placeholder_text' => 'Type your message...',
            'show_branding' => 1,
            'allowed_domains' => null,
            'is_active' => 1,
        ]);

        $bot = Bot::fromArray($this->bots->find($botId));

        $this->webhooks->dispatch('bot.created', ['bot_id' => $bot->uuid, 'name' => $bot->name, 'user_id' => $userId]);

        return $bot;
    }

    public function getForUser(string $uuid, int $userId): Bot
    {
        $row = $this->bots->findByUuidForUser($uuid, $userId);

        if ($row === null) {
            throw new NotFoundException('Bot not found.');
        }

        return Bot::fromArray($row);
    }

    /**
     * @return array{data: list<Bot>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        $result = $this->bots->paginateForUser($userId, $page, $perPage);
        $result['data'] = array_map(Bot::fromArray(...), $result['data']);

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $uuid, int $userId, array $data): Bot
    {
        $bot = $this->getForUser($uuid, $userId);

        $allowed = array_intersect_key($data, array_flip([
            'name', 'description', 'system_prompt', 'model', 'temperature', 'max_output_tokens',
            'top_p', 'top_k', 'safety_settings', 'language', 'personality', 'tone',
            'welcome_message', 'primary_color', 'status', 'is_public',
        ]));

        if (isset($allowed['safety_settings']) && is_array($allowed['safety_settings'])) {
            $allowed['safety_settings'] = json_encode($allowed['safety_settings']);
        }

        if (!empty($allowed)) {
            $this->bots->update($bot->id, $allowed);
        }

        return Bot::fromArray($this->bots->find($bot->id));
    }

    public function delete(string $uuid, int $userId): void
    {
        $bot = $this->getForUser($uuid, $userId);
        $this->bots->delete($bot->id);
        $this->webhooks->dispatch('bot.deleted', ['bot_id' => $bot->uuid, 'name' => $bot->name, 'user_id' => $userId]);
    }

    public function findPublicByUuid(string $uuid): Bot
    {
        $row = $this->bots->findByUuid($uuid);

        if ($row === null || $row['status'] !== 'active') {
            throw new NotFoundException('Bot not found or is not active.');
        }

        return Bot::fromArray($row);
    }

    private function defaultSystemPrompt(): string
    {
        return 'You are a helpful, friendly assistant. Answer questions accurately and concisely '
            . 'based on the knowledge base provided to you. If you do not know the answer, say so honestly '
            . 'rather than making something up.';
    }
}