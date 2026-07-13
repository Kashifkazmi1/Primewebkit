<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a plan usage limit (bots, messages, storage, etc.) or
 * feature gate (api_access, streaming, white_label...) blocks an
 * action. Uses 402 Payment Required — the most semantically accurate
 * status for "this requires a higher plan," distinct from 403
 * (permission denied) and 429 (rate limited).
 */
class UsageLimitExceededException extends ApiException
{
    public function __construct(string $message, private readonly string $metric)
    {
        parent::__construct($message, 402, 'USAGE_LIMIT_EXCEEDED');
    }

    public function getMetric(): string
    {
        return $this->metric;
    }
}
