<?php

declare(strict_types=1);

namespace App\DTO\AI;

/**
 * A provider-agnostic chat completion request. Every AIProvider
 * implementation must accept this exact shape, translating it into
 * its own wire format internally — business logic (RagPipeline,
 * PromptEngine, controllers) never constructs provider-specific
 * payloads directly.
 */
final class ChatRequest
{
    /**
     * @param list<ChatMessage> $messages Conversation history + current user turn, oldest first.
     * @param array<string, mixed> $safetySettings Provider-specific safety configuration, passed through as-is.
     */
    public function __construct(
        public readonly string $model,
        public readonly ?string $systemPrompt,
        public readonly array $messages,
        public readonly float $temperature = 0.7,
        public readonly int $maxOutputTokens = 2048,
        public readonly float $topP = 0.95,
        public readonly int $topK = 40,
        public readonly array $safetySettings = [],
    ) {
    }
}
