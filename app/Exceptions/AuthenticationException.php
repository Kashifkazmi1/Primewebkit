<?php

declare(strict_types=1);

namespace App\Exceptions;

class AuthenticationException extends ApiException
{
    public function __construct(string $message = 'Unauthenticated.', string $errorCode = 'UNAUTHENTICATED')
    {
        parent::__construct($message, 401, $errorCode);
    }
}
