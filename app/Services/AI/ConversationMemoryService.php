<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AI\ChatMessage;
use App\Services\ConversationService;

/**
 * Short-term conversation memory: fetches the most recent N messages
 * (configurable window) and trims them to fit a token budget before
 * they're sent to the AI provider as prior turns. This is the single
 * place both concerns (message-count window and token-budget
 * trimming) come together, used by ChatOrchestratorService.
 */
final class ConversationMemoryService
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly PromptEngineService $promptEngine,
    ) {
    }

    /**
     * @return list<ChatMessage>
     */
    public function recall(int $conversationId, int $skipMostRecent = 0): array
    {
        $maxMessages = (int) config('ai.memory.max_messages', 20);
        $maxTokens = (int) config('ai.memory.max_tokens', 3000);

        $history = $this->conversations->recentContext($conversationId, $maxMessages + $skipMostRecent);

        if ($skipMostRecent > 0) {
            $history = array_slice($history, 0, max(0, count($history) - $skipMostRecent));
        }

        return $this->promptEngine->buildConversationMemory($history, $maxTokens);
    }
}
