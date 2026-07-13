<?php

declare(strict_types=1);

namespace App\Exceptions;

class AuthorizationException extends ApiException
{
    public function __construct(string $message = 'This action is unauthorized.')
    {
        parent::__construct($message, 403, 'FORBIDDEN');
    }
}
