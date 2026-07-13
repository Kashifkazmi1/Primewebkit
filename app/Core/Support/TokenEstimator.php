<?php

declare(strict_types=1);

namespace App\Core\Support;

/**
 * Approximate token counting used for prompt-budget management
 * (deciding how much conversation history / knowledge context fits
 * before calling the AI provider) and for estimating usage before a
 * response comes back. This is deliberately a heuristic (~4 characters
 * per token for English text, matching OpenAI/Google's published
 * rule of thumb) — actual billed/counted tokens come from the
 * provider's `usageMetadata` on every real response and are what gets
 * persisted to `ai_usage_logs`. This estimator is only ever used for
 * pre-flight budgeting, never for billing records.
 */
final class TokenEstimator
{
    private const CHARS_PER_TOKEN = 4.0;

    public static function estimate(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        return (int) ceil(mb_strlen($text) / self::CHARS_PER_TOKEN);
    }

    /**
     * @param list<string> $texts
     */
    public static function estimateMany(array $texts): int
    {
        $total = 0;

        foreach ($texts as $text) {
            $total += self::estimate($text);
        }

        return $total;
    }
}
