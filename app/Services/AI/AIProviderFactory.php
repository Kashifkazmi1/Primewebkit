<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Core\Container;
use App\Core\Contracts\AIChatProviderInterface;
use App\Core\Contracts\AIEmbeddingProviderInterface;
use App\Services\AI\Providers\GeminiProvider;
use RuntimeException;

/**
 * Resolves the configured AI provider by name. This is the ONLY place
 * that maps a provider identifier (as stored on `bots.ai_provider` or
 * `config('gemini...')`-style per-provider config) to a concrete
 * class. Adding OpenAI or Claude support later means:
 *   1. `app/Services/AI/Providers/OpenAiProvider.php` implementing
 *      the same interfaces as GeminiProvider,
 *   2. one new `case` below,
 *   3. a `config/openai.php` file.
 * Nothing in RagPipelineService, PromptEngineService,
 * ChatOrchestratorService, or any controller changes.
 */
final class AIProviderFactory
{
    /**
     * @var array<string, AIChatProviderInterface&AIEmbeddingProviderInterface>
     */
    private array $resolved = [];

    public function __construct(private readonly Container $container)
    {
    }

    public function chatProvider(string $name): AIChatProviderInterface
    {
        $provider = $this->resolve($name);

        if (!$provider instanceof AIChatProviderInterface) {
            throw new RuntimeException("AI provider [{$name}] does not support chat.");
        }

        return $provider;
    }

    public function embeddingProvider(string $name): AIEmbeddingProviderInterface
    {
        $provider = $this->resolve($name);

        if (!$provider instanceof AIEmbeddingProviderInterface) {
            throw new RuntimeException("AI provider [{$name}] does not support embeddings.");
        }

        return $provider;
    }

    private function resolve(string $name): object
    {
        $key = strtolower($name);

        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        $provider = match ($key) {
            'gemini' => $this->container->resolve(GeminiProvider::class),
            default => throw new RuntimeException("Unknown AI provider [{$name}]. Supported: gemini."),
        };

        return $this->resolved[$key] = $provider;
    }
}
