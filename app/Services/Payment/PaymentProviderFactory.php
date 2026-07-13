<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Core\Container;
use App\Core\Contracts\PaymentProviderInterface;
use RuntimeException;

/**
 * Resolves the active PaymentProviderInterface implementation by
 * name, exactly mirroring AIProviderFactory's pattern for AI
 * providers. Adding Stripe: write StripePaymentProvider implementing
 * PaymentProviderInterface, add one match arm below, set
 * BILLING_PROVIDER=stripe. SubscriptionService and every controller
 * depend only on the interface.
 */
final class PaymentProviderFactory
{
    private ?PaymentProviderInterface $resolved = null;

    public function __construct(private readonly Container $container)
    {
    }

    public function provider(): PaymentProviderInterface
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $name = strtolower((string) config('billing.default_provider', 'manual'));

        return $this->resolved = match ($name) {
            'manual' => $this->container->resolve(ManualPaymentProvider::class),
            default => throw new RuntimeException("Unknown payment provider [{$name}]. Supported: manual."),
        };
    }
}
