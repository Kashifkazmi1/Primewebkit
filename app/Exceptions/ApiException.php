<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Base class for all exceptions that should be translated into a
 * well-formed JSON API error response by the central ExceptionHandler.
 *
 * Any exception NOT extending this class is treated as an
 * unexpected/internal error (HTTP 500) and is never leaked to clients
 * with its raw message when app.debug is false.
 */
abstract class ApiException extends \RuntimeException
{
    /**
     * @param array<string, list<string>>|array<int, string> $errors
     */
    public function __construct(
        string $message,
        protected readonly int $statusCode = 500,
        protected readonly string $errorCode = 'INTERNAL_ERROR',
        protected readonly array $errors = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, list<string>>|array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
