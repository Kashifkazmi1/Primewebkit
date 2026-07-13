<?php

declare(strict_types=1);

namespace App\Exceptions;

class ConflictException extends ApiException
{
    public function __construct(string $message = 'The request could not be completed due to a conflict.')
    {
        parent::__construct($message, 409, 'CONFLICT');
    }
}
