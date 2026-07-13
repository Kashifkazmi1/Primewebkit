<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\DTO\AI\ChatRequest;
use App\DTO\AI\ChatResponse;
use Closure;

/**
 * Contract every chat-capable AI provider must implement (Gemini,
 * and later OpenAI/Claude/etc.). Business logic — RagPipelineService,
 * PromptEngineService, controllers — depends only on this interface,
 * never on a concrete provider class, so adding a new provider never
 * requires touching business logic, only:
 *   1. a new class implementing this interface, and
 *   2. a new `case` in AIProviderFactory.
 */
interface AIChatProviderInterface
{
    public function chat(ChatRequest $request): ChatResponse;

    /**
     * Streams the response, invoking $onChunk for each incremental
     * piece of text as it arrives. Returns the final aggregated
     * ChatResponse once the stream completes.
     *
     * @param Closure(\App\DTO\AI\StreamChunk): void $onChunk
     * @param (Closure(): bool)|null $shouldStop Polled between chunks; returning true aborts the stream early (used for "stop generation").
     */
    public function chatStream(ChatRequest $request, Closure $onChunk, ?Closure $shouldStop = null): ChatResponse;

    public function providerName(): string;
}
