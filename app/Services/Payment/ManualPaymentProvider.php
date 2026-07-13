<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Core\Contracts\PaymentProviderInterface;
use App\DTO\Payment\CheckoutSession;
use App\DTO\Payment\PaymentResult;
use App\DTO\Payment\WebhookEvent;
use App\Exceptions\ExternalServiceException;
use App\Models\Plan;
use App\Models\Subscription;

/**
 * Fully functional billing provider that requires no external
 * payment gateway: "checkout" activates the subscription immediately
 * (an admin or the account owner is trusted to arrange payment
 * out-of-band — bank transfer, invoice, cash, etc.), and there is no
 * webhook signature to verify since there's no external party sending
 * one. This is what makes the platform usable for billing on day one
 * without Stripe/Paddle/etc. configured, while `PaymentProviderInterface`
 * keeps the door open to add a real gateway later without touching
 * `SubscriptionService`.
 */
final class ManualPaymentProvider implements PaymentProviderInterface
{
    public function providerName(): string
    {
        return 'manual';
    }

    public function ensureCustomer(int $userId, string $email, string $name): string
    {
        // No external customer record needed — the user's own id is
        // the "customer id" for manual billing.
        return "manual_customer_{$userId}";
    }

    public function startCheckout(Plan $plan, string $billingCycle, string $customerId, string $successUrl, string $cancelUrl): CheckoutSession
    {
        // No redirect/external checkout step — SubscriptionService
        // activates the subscription directly and this just confirms
        // that decision back to the caller.
        return new CheckoutSession(
            sessionId: 'manual_' . str_uuid4(),
            redirectUrl: $successUrl,
            requiresRedirect: false,
        );
    }

    public function cancelSubscription(Subscription $subscription, bool $atPeriodEnd = true): PaymentResult
    {
        // Nothing external to call — SubscriptionService updates the
        // local row directly. This just reports success.
        return new PaymentResult(
            success: true,
            status: $atPeriodEnd ? 'will_cancel_at_period_end' : 'canceled',
            providerReferenceId: $subscription->providerSubscriptionId,
        );
    }

    public function parseWebhookEvent(string $rawPayload, string $signatureHeader): WebhookEvent
    {
        // Manual billing has no external party sending webhooks, so
        // there is nothing legitimate to receive here.
        throw new ExternalServiceException(
            'ManualPaymentProvider',
            'The manual payment provider does not receive webhooks. Configure a real payment gateway to use webhook-driven billing events.'
        );
    }

    public function refund(string $providerTransactionId, ?float $amount = null): PaymentResult
    {
        // Nothing external to call — TransactionService marks the
        // local transaction row refunded; this just confirms it.
        return new PaymentResult(
            success: true,
            status: 'refunded',
            providerReferenceId: $providerTransactionId,
        );
    }
}
