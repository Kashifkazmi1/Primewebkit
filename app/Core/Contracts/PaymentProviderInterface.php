<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\DTO\Payment\CheckoutSession;
use App\DTO\Payment\PaymentResult;
use App\DTO\Payment\WebhookEvent;
use App\Models\Plan;
use App\Models\Subscription;

/**
 * Contract every billing provider must implement. The platform ships
 * with `ManualPaymentProvider` (self-serve/offline billing — an admin
 * or the account owner manages the subscription lifecycle directly,
 * no external gateway involved) so the platform is fully usable
 * before any payment gateway is connected.
 *
 * Adding Stripe (or another gateway) later means writing one class
 * implementing this interface (`App\Services\Payment\StripePaymentProvider`)
 * and changing one binding in `bootstrap/bindings.php` — nothing in
 * `SubscriptionService`, `InvoiceService`, or any controller changes.
 * This interface is intentionally shaped around concepts every major
 * payment gateway (Stripe, Paddle, LemonSqueezy) exposes: a customer
 * record, a checkout/subscription session, and signed webhook events.
 */
interface PaymentProviderInterface
{
    public function providerName(): string;

    /**
     * Ensures a customer record exists on the provider's side for this
     * user, returning the provider's customer id (used for future
     * charges/subscriptions). Providers that don't need this (manual
     * billing) can simply return a locally-generated id.
     */
    public function ensureCustomer(int $userId, string $email, string $name): string;

    /**
     * Begins a checkout flow for a subscription to the given plan.
     * For redirect-based providers (Stripe Checkout) this returns a
     * URL the frontend should redirect to. For the manual provider,
     * it activates the subscription immediately and returns a
     * confirmation "url" that's really just a dashboard route.
     */
    public function startCheckout(Plan $plan, string $billingCycle, string $customerId, string $successUrl, string $cancelUrl): CheckoutSession;

    /**
     * Cancels a subscription on the provider's side (or immediately,
     * for manual billing). $atPeriodEnd=true lets the subscription
     * keep working until the current period expires rather than
     * cutting off access immediately.
     */
    public function cancelSubscription(Subscription $subscription, bool $atPeriodEnd = true): PaymentResult;

    /**
     * Verifies and parses an incoming webhook payload from the
     * provider (e.g. Stripe's signed webhook body) into a normalized
     * WebhookEvent. Throws if signature verification fails.
     */
    public function parseWebhookEvent(string $rawPayload, string $signatureHeader): WebhookEvent;

    /**
     * Issues a refund for a transaction. Manual billing simply marks
     * the transaction refunded locally (no external call).
     */
    public function refund(string $providerTransactionId, ?float $amount = null): PaymentResult;
}
