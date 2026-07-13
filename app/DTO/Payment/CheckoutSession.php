<?php

declare(strict_types=1);

namespace App\DTO\Payment;

final class CheckoutSession
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $redirectUrl,
        public readonly bool $requiresRedirect,
    ) {
    }
}
