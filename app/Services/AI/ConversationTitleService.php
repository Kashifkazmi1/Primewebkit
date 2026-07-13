<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AI\ChatMessage;
use App\DTO\AI\ChatRequest;
use App\Models\Bot;
use App\Repositories\ConversationRepository;
use App\Core\Logging\LoggerFactory;
use Throwable;

/**
 * Auto-generates a short, human-readable title for a conversation
 * (shown in the dashboard's conversation list) once there's enough
 * content to summarize. Falls back to truncating the first user
 * message if the AI call fails.
 */
final class ConversationTitleService
{
    private const MAX_TITLE_LENGTH = 60;

    public function __construct(
        private readonly AIProviderFactory $providers,
        private readonly ConversationRepository $conversations,
    ) {
    }

    public function generateAndStore(Bot $bot, int $conversationId, string $firstUserMessage): void
    {
        $title = $this->generate($bot, $firstUserMessage);
        $this->conversations->update($conversationId, ['title' => $title]);
    }

    private function generate(Bot $bot, string $firstUserMessage): string
    {
        try {
            $provider = $this->providers->chatProvider($bot->aiProvider);

            $response = $provider->chat(new ChatRequest(
                model: $bot->model,
                systemPrompt: null,
                messages: [new ChatMessage(
                    'user',
                    "Summarize this message as a short conversation title, max 6 words, no punctuation at the end, "
                        . "no quotes around it. Message: \"{$firstUserMessage}\""
                )],
                temperature: 0.3,
                maxOutputTokens: 30,
            ));

            $title = trim($response->content, " \t\n\r\0\x0B\"'");

            return $title !== '' ? mb_substr($title, 0, self::MAX_TITLE_LENGTH) : $this->fallbackTitle($firstUserMessage);
        } catch (Throwable $e) {
            LoggerFactory::channel('ai')->warning('Conversation title generation failed; using fallback.', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackTitle($firstUserMessage);
        }
    }

    private function fallbackTitle(string $message): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

        return mb_strlen($title) > self::MAX_TITLE_LENGTH
            ? mb_substr($title, 0, self::MAX_TITLE_LENGTH - 1) . '…'
            : $title;
    }
}
