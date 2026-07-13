<?php

declare(strict_types=1);

namespace App\Services\AI;

final class CostEstimatorService
{
    public function estimate(string $provider, string $model, int $promptTokens, int $completionTokens): float
    {
        $pricing = config("ai_pricing.{$provider}.{$model}") ?? config('ai_pricing.default');

        $promptCost = ($promptTokens / 1000) * ($pricing['prompt_per_1k'] ?? 0.0);
        $completionCost = ($completionTokens / 1000) * ($pricing['completion_per_1k'] ?? 0.0);

        return round($promptCost + $completionCost, 6);
    }
}
