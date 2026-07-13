<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AI\ChatMessage;
use App\DTO\AI\ChatRequest;
use App\Models\Bot;
use App\Core\Logging\LoggerFactory;
use Throwable;

/**
 * Generates short follow-up question suggestions for a visitor to tap
 * instead of typing, based on the conversation so far. Falls back to
 * generic, bot-agnostic suggestions if the AI call fails — this is a
 * UX nicety, never worth failing the whole chat turn over.
 */
final class SuggestedQuestionsService
{
    private const FALLBACK_QUESTIONS = [
        'Can you tell me more about that?',
        'What are your business hours?',
        'How do I contact support?',
    ];

    public function __construct(private readonly AIProviderFactory $providers)
    {
    }

    /**
     * @param list<array{role: string, content: string}> $recentHistory
     * @return list<string>
     */
    public function suggest(Bot $bot, array $recentHistory): array
    {
        if (empty($recentHistory)) {
            return self::FALLBACK_QUESTIONS;
        }

        try {
            $provider = $this->providers->chatProvider($bot->aiProvider);

            $transcript = implode("\n", array_map(
                fn (array $m) => ucfirst($m['role']) . ': ' . $m['content'],
                array_slice($recentHistory, -6)
            ));

            $prompt = "Based on this conversation transcript, suggest exactly 3 short, natural follow-up "
                . "questions the user might want to ask next. Reply with ONLY the 3 questions, one per line, "
                . "no numbering, no extra commentary.\n\nTranscript:\n{$transcript}";

            $response = $provider->chat(new ChatRequest(
                model: $bot->model,
                systemPrompt: null,
                messages: [new ChatMessage('user', $prompt)],
                temperature: 0.5,
                maxOutputTokens: 150,
            ));

            $lines = array_values(array_filter(
                array_map('trim', explode("\n", $response->content)),
                fn (string $line) => $line !== ''
            ));

            return !empty($lines) ? array_slice($lines, 0, 3) : self::FALLBACK_QUESTIONS;
        } catch (Throwable $e) {
            LoggerFactory::channel('ai')->warning('Suggested-questions generation failed; using fallback.', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return self::FALLBACK_QUESTIONS;
        }
    }
}
