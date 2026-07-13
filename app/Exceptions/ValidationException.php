<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when request input fails validation.
 *
 * @see \App\Core\Validation\Validator
 */
class ValidationException extends ApiException
{
    /**
     * @param array<string, list<string>> $errors field => list of messages
     */
    public function __construct(array $errors, string $message = 'The given data was invalid.')
    {
        parent::__construct($message, 422, 'VALIDATION_ERROR', $errors);
    }
}
