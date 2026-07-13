<?php

declare(strict_types=1);

namespace App\Exceptions;

class RateLimitException extends ApiException
{
    public function __construct(
        string $message = 'Too many requests. Please try again later.',
        private readonly int $retryAfterSeconds = 60
    ) {
        parent::__construct($message, 429, 'RATE_LIMITED');
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
