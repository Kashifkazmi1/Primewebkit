<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when an upstream integration (e.g. Google Gemini, Stripe, SMTP)
 * fails or returns an unexpected response.
 */
class ExternalServiceException extends ApiException
{
    public function __construct(string $service, string $message, ?\Throwable $previous = null)
    {
        parent::__construct(
            "[{$service}] {$message}",
            502,
            'EXTERNAL_SERVICE_ERROR',
            [],
            $previous
        );
    }
}
