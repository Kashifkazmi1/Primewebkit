<?php

declare(strict_types=1);

/**
 * Per-1,000-token pricing used only to populate `estimated_cost` on
 * ai_usage_logs for visibility/budgeting — it does not affect billing
 * of the platform's own customers (see Plans/Subscriptions, Phase 5).
 * Gemini's free tier is $0 by default; update these when a paid tier
 * or additional providers are enabled.
 */
return [
    'gemini' => [
        'gemini-1.5-flash' => [
            'prompt_per_1k' => (float) env('PRICING_GEMINI_FLASH_PROMPT_PER_1K', '0'),
            'completion_per_1k' => (float) env('PRICING_GEMINI_FLASH_COMPLETION_PER_1K', '0'),
        ],
        'gemini-1.5-pro' => [
            'prompt_per_1k' => (float) env('PRICING_GEMINI_PRO_PROMPT_PER_1K', '0'),
            'completion_per_1k' => (float) env('PRICING_GEMINI_PRO_COMPLETION_PER_1K', '0'),
        ],
        'text-embedding-004' => [
            'prompt_per_1k' => (float) env('PRICING_GEMINI_EMBEDDING_PER_1K', '0'),
            'completion_per_1k' => 0.0,
        ],
    ],

    /**
     * Fallback used when the specific model isn't listed above.
     */
    'default' => [
        'prompt_per_1k' => 0.0,
        'completion_per_1k' => 0.0,
    ],
];
