<?php

declare(strict_types=1);

namespace App\DTO\Payment;

final class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $providerReferenceId = null,
        public readonly ?string $message = null,
    ) {
    }
}
