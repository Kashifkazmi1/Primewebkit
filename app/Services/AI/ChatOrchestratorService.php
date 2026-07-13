<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AI\ChatMessage;
use App\DTO\AI\ChatRequest;
use App\DTO\AI\ChatResponse;
use App\DTO\AI\StreamChunk;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Models\Bot;
use App\Models\Message;
use App\Repositories\ConversationRepository;
use App\Repositories\MessageRepository;
use App\Services\ConversationService;
use App\Services\UsageCounterService;
use App\Services\WebhookDispatcherService;
use Closure;
use Throwable;

/**
 * The single entry point for turning a user's chat message into an
 * AI-generated reply. Wires together, in order:
 *   RagPipelineService (retrieval) -> PromptEngineService (assembly)
 *   -> ConversationMemoryService (history) -> AIProviderFactory
 *   (generation) -> ConversationService (persistence) ->
 *   AIUsageLoggerService (tracking).
 *
 * IMPORTANT ordering rule: conversation memory is always recalled
 * BEFORE the current turn's user message is persisted (or, for
 * regenerate, with the already-persisted current-turn message
 * explicitly excluded via skipMostRecent). Getting this backwards
 * would duplicate the current turn — once from history, once as the
 * explicit final message — which was caught and fixed during Phase 4
 * development before this ever reached a real AI call.
 */
final class ChatOrchestratorService
{
    public function __construct(
        private readonly RagPipelineService $rag,
        private readonly PromptEngineService $promptEngine,
        private readonly ConversationMemoryService $memory,
        private readonly AIProviderFactory $providers,
        private readonly PromptSecurityService $security,
        private readonly ConversationService $conversations,
        private readonly ConversationRepository $conversationRepository,
        private readonly MessageRepository $messages,
        private readonly AIUsageLoggerService $usageLogger,
        private readonly UsageCounterService $usageCounters,
        private readonly WebhookDispatcherService $webhooks,
    ) {
    }

    /**
     * Persists the user's message, generates a reply, persists the
     * reply. Used by the non-streaming send-message endpoint.
     *
     * @return array{userMessage: Message, assistantMessage: Message, chatResponse: ChatResponse}
     */
    public function handleUserMessage(Bot $bot, int $conversationId, string $rawUserMessage): array
    {
        $sanitized = $this->security->sanitizeUserInput($rawUserMessage);

        // Memory recalled BEFORE persisting the new user message, so
        // it reflects only prior turns.
        $memoryMessages = $this->memory->recall($conversationId);

        $chatRequest = $this->buildChatRequest($bot, $sanitized, $memoryMessages);

        $isNewConversation = (int) $this->conversationRepository->find($conversationId)['message_count'] === 0;

        $userMessage = $this->conversations->appendMessage($conversationId, 'user', $sanitized);
        $this->usageCounters->incrementMessages($bot->userId);

        if ($isNewConversation) {
            $this->webhooks->dispatch('chat.started', ['bot_id' => $bot->uuid, 'conversation_id' => $conversationId]);
        }

        $requestStart = microtime(true);
        $provider = $this->providers->chatProvider($bot->aiProvider);

        try {
            $response = $provider->chat($chatRequest);
        } catch (Throwable $e) {
            $this->logFailure($bot, $conversationId, 'chat', $e, $requestStart);

            throw $e;
        }

        $assistantMessage = $this->persistAssistantReplyAndLog($bot, $conversationId, $response, $requestStart);

        $this->webhooks->dispatch('chat.completed', [
            'bot_id' => $bot->uuid,
            'conversation_id' => $conversationId,
            'message' => $assistantMessage->content,
        ]);

        return ['userMessage' => $userMessage, 'assistantMessage' => $assistantMessage, 'chatResponse' => $response];
    }

    /**
     * Streaming variant of handleUserMessage(). $onChunk is invoked
     * for every incremental piece of text as it arrives.
     *
     * @param Closure(StreamChunk): void $onChunk
     * @param (Closure(): bool)|null $shouldStop
     * @return array{userMessage: Message, assistantMessage: Message, chatResponse: ChatResponse}
     */
    public function handleUserMessageStream(Bot $bot, int $conversationId, string $rawUserMessage, Closure $onChunk, ?Closure $shouldStop = null): array
    {
        $sanitized = $this->security->sanitizeUserInput($rawUserMessage);
        $memoryMessages = $this->memory->recall($conversationId);
        $chatRequest = $this->buildChatRequest($bot, $sanitized, $memoryMessages);

        $isNewConversation = (int) $this->conversationRepository->find($conversationId)['message_count'] === 0;

        $userMessage = $this->conversations->appendMessage($conversationId, 'user', $sanitized);
        $this->usageCounters->incrementMessages($bot->userId);

        if ($isNewConversation) {
            $this->webhooks->dispatch('chat.started', ['bot_id' => $bot->uuid, 'conversation_id' => $conversationId]);
        }

        $requestStart = microtime(true);
        $provider = $this->providers->chatProvider($bot->aiProvider);

        $this->conversationRepository->markGenerating($conversationId);

        $combinedShouldStop = function () use ($conversationId, $shouldStop): bool {
            if ($shouldStop !== null && $shouldStop()) {
                return true;
            }

            return $this->conversationRepository->isCancellationRequested($conversationId);
        };

        try {
            $response = $provider->chatStream($chatRequest, $onChunk, $combinedShouldStop);
        } catch (Throwable $e) {
            $this->conversationRepository->clearGenerating($conversationId);
            $this->logFailure($bot, $conversationId, 'chat_stream', $e, $requestStart);

            throw $e;
        }

        $this->conversationRepository->clearGenerating($conversationId);
        $assistantMessage = $this->persistAssistantReplyAndLog($bot, $conversationId, $response, $requestStart, 'chat_stream');

        $this->webhooks->dispatch('chat.completed', [
            'bot_id' => $bot->uuid,
            'conversation_id' => $conversationId,
            'message' => $assistantMessage->content,
        ]);

        return ['userMessage' => $userMessage, 'assistantMessage' => $assistantMessage, 'chatResponse' => $response];
    }

    /**
     * Deletes the most recent assistant message (if any) and
     * re-generates a fresh reply to the last user message, without
     * duplicating that user message in either storage or the prompt
     * sent to the provider.
     *
     * @return array{assistantMessage: Message, chatResponse: ChatResponse}
     */
    public function regenerate(Bot $bot, int $conversationId): array
    {
        if ($this->conversationRepository->isGenerating($conversationId)) {
            throw new ConflictException('This conversation is still generating a response.');
        }

        $last = $this->messages->last($conversationId);

        if ($last === null) {
            throw new NotFoundException('This conversation has no messages to regenerate.');
        }

        if ($last['role'] === 'assistant') {
            $this->messages->deleteById((int) $last['id']);
            $this->conversationRepository->decrementMessageCount($conversationId);
            $last = $this->messages->last($conversationId);
        }

        if ($last === null || $last['role'] !== 'user') {
            throw new NotFoundException('No prior user message found to regenerate a response for.');
        }

        $sanitized = $this->security->sanitizeUserInput((string) $last['content']);

        // The user message is already persisted (it's $last) — skip it
        // when recalling history, since it's re-added explicitly below
        // as the current turn.
        $memoryMessages = $this->memory->recall($conversationId, skipMostRecent: 1);
        $chatRequest = $this->buildChatRequest($bot, $sanitized, $memoryMessages);

        $requestStart = microtime(true);
        $provider = $this->providers->chatProvider($bot->aiProvider);

        try {
            $response = $provider->chat($chatRequest);
        } catch (Throwable $e) {
            $this->logFailure($bot, $conversationId, 'chat', $e, $requestStart);

            throw $e;
        }

        $assistantMessage = $this->persistAssistantReplyAndLog($bot, $conversationId, $response, $requestStart);

        return ['assistantMessage' => $assistantMessage, 'chatResponse' => $response];
    }

    /**
     * @param list<ChatMessage> $memoryMessages
     */
    private function buildChatRequest(Bot $bot, string $sanitizedUserMessage, array $memoryMessages): ChatRequest
    {
        $retrievedChunks = $this->rag->retrieve($bot, $sanitizedUserMessage);

        $maxContextTokens = (int) config('ai.rag.max_context_tokens', 2000);
        $contextBlock = $this->promptEngine->buildContextBlock($retrievedChunks, $maxContextTokens);
        $systemPrompt = $this->promptEngine->buildSystemPrompt($bot, $contextBlock);

        $safetySettings = !empty($bot->safetySettings) ? $bot->safetySettings : (array) config('ai.default_safety_settings', []);

        return new ChatRequest(
            model: $bot->model,
            systemPrompt: $systemPrompt,
            messages: [...$memoryMessages, new ChatMessage('user', $sanitizedUserMessage)],
            temperature: $bot->temperature,
            maxOutputTokens: $bot->maxOutputTokens,
            topP: $bot->topP,
            topK: $bot->topK,
            safetySettings: $safetySettings,
        );
    }

    private function persistAssistantReplyAndLog(Bot $bot, int $conversationId, ChatResponse $response, float $requestStart, string $operation = 'chat'): Message
    {
        $content = $response->wasBlocked
            ? $this->blockedResponseMessage()
            : $response->content;

        $message = $this->conversations->appendMessage(
            $conversationId,
            'assistant',
            $content,
            $response->totalTokens,
            $response->latencyMs
        );

        $requestDurationMs = (int) round((microtime(true) - $requestStart) * 1000);

        $this->usageLogger->logChatResponse(
            $bot->id,
            $conversationId,
            $message->id,
            $operation,
            $response,
            $requestDurationMs
        );

        $this->usageCounters->incrementAiRequests($bot->userId);

        return $message;
    }

    private function logFailure(Bot $bot, int $conversationId, string $operation, Throwable $e, float $requestStart): void
    {
        $this->usageLogger->logFailure(
            $bot->id,
            $conversationId,
            $bot->aiProvider,
            $bot->model,
            $operation,
            $e->getMessage(),
            (int) round((microtime(true) - $requestStart) * 1000)
        );
    }

    private function blockedResponseMessage(): string
    {
        return "I'm not able to respond to that. Could you rephrase your question?";
    }
}
