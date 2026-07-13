<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Core\Logging\LoggerFactory;
use App\DTO\AI\ChatResponse;
use App\Repositories\AIUsageLogRepository;

/**
 * Records every AI provider call — successful or not — to
 * `ai_usage_logs`, and mirrors a line to the dedicated `ai` log
 * channel for tailing/debugging without a DB query.
 */
final class AIUsageLoggerService
{
    public function __construct(
        private readonly AIUsageLogRepository $usageLogs,
        private readonly CostEstimatorService $costEstimator,
    ) {
    }

    public function logChatResponse(
        int $botId,
        ?int $conversationId,
        ?int $messageId,
        string $operation,
        ChatResponse $response,
        int $requestDurationMs
    ): void {
        $status = $response->wasBlocked ? 'blocked' : 'success';

        $cost = $this->costEstimator->estimate(
            $response->provider,
            $response->model,
            $response->promptTokens ?? 0,
            $response->completionTokens ?? 0
        );

        $this->usageLogs->create([
            'uuid' => str_uuid4(),
            'bot_id' => $botId,
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'provider' => $response->provider,
            'model' => $response->model,
            'operation' => $operation,
            'prompt_tokens' => $response->promptTokens,
            'completion_tokens' => $response->completionTokens,
            'total_tokens' => $response->totalTokens,
            'request_duration_ms' => $requestDurationMs,
            'response_duration_ms' => $response->latencyMs,
            'estimated_cost' => $cost,
            'status' => $status,
            'error_message' => $response->wasBlocked ? "Blocked: {$response->blockReason}" : null,
            'created_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);

        LoggerFactory::channel('ai')->info('AI request completed.', [
            'bot_id' => $botId,
            'provider' => $response->provider,
            'model' => $response->model,
            'operation' => $operation,
            'total_tokens' => $response->totalTokens,
            'latency_ms' => $response->latencyMs,
            'status' => $status,
        ]);
    }

    public function logFailure(
        int $botId,
        ?int $conversationId,
        string $provider,
        string $model,
        string $operation,
        string $errorMessage,
        int $requestDurationMs
    ): void {
        $this->usageLogs->create([
            'uuid' => str_uuid4(),
            'bot_id' => $botId,
            'conversation_id' => $conversationId,
            'message_id' => null,
            'provider' => $provider,
            'model' => $model,
            'operation' => $operation,
            'prompt_tokens' => null,
            'completion_tokens' => null,
            'total_tokens' => null,
            'request_duration_ms' => $requestDurationMs,
            'response_duration_ms' => null,
            'estimated_cost' => 0,
            'status' => 'failed',
            'error_message' => mb_substr($errorMessage, 0, 2000),
            'created_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);

        LoggerFactory::channel('ai')->error('AI request failed.', [
            'bot_id' => $botId,
            'provider' => $provider,
            'model' => $model,
            'operation' => $operation,
            'error' => $errorMessage,
        ]);
    }
}
