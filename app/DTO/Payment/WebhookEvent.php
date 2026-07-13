<?php

declare(strict_types=1);

namespace App\DTO\Payment;

final class WebhookEvent
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $type,
        public readonly array $data,
        public readonly string $providerEventId,
    ) {
    }
}
