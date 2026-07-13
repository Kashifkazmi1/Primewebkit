<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Repositories\CouponRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\PlanRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\UserRepository;
use App\Services\Payment\PaymentProviderFactory;
use DateTimeImmutable;

/**
 * Owns the subscription lifecycle: creation (with optional trial),
 * cancellation (immediate or at period end), renewal, past-due/grace
 * period handling, and coupon redemption. Delegates any actual
 * payment-gateway interaction to PaymentProviderInterface — this
 * class never talks to Stripe (or anything else) directly.
 */
final class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions,
        private readonly PlanRepository $plans,
        private readonly UserRepository $users,
        private readonly CouponRepository $coupons,
        private readonly InvoiceRepository $invoices,
        private readonly InvoiceService $invoiceService,
        private readonly PaymentProviderFactory $paymentProviders,
        private readonly NotificationService $notifications,
        private readonly WebhookDispatcherService $webhooks,
    ) {
    }

    public function currentForUser(int $userId): ?Subscription
    {
        $row = $this->subscriptions->activeForUser($userId);

        return $row !== null ? Subscription::fromArray($row) : null;
    }

    /**
     * @return list<Subscription>
     */
    public function historyForUser(int $userId): array
    {
        return array_map(Subscription::fromArray(...), $this->subscriptions->forUser($userId));
    }

    /**
     * Subscribes a user to a plan, replacing any existing active
     * subscription (no stacking). Applies a trial period if the plan
     * has one and the user has never had a subscription before, and
     * applies a coupon if a valid code is supplied.
     */
    public function subscribe(int $userId, string $planUuid, string $billingCycle, ?string $couponCode = null): Subscription
    {
        $planRow = $this->plans->findByUuid($planUuid);

        if ($planRow === null) {
            throw new NotFoundException('Plan not found.');
        }

        $plan = Plan::fromArray($planRow);
        $userRow = $this->users->find($userId);

        if ($userRow === null) {
            throw new NotFoundException('User not found.');
        }

        if (!in_array($billingCycle, ['monthly', 'yearly'], true)) {
            throw new ValidationException(['billing_cycle' => ['Billing cycle must be monthly or yearly.']]);
        }

        $existing = $this->subscriptions->activeForUser($userId);
        $isFirstSubscription = empty($this->subscriptions->forUser($userId));

        $provider = $this->paymentProviders->provider();
        $customerId = $provider->ensureCustomer($userId, $userRow['email'], $userRow['name']);

        $now = now_utc();
        $useTrial = $isFirstSubscription && $plan->trialDays > 0;
        $periodEnd = $useTrial
            ? $now->modify("+{$plan->trialDays} days")
            : $this->periodEndFor($now, $billingCycle);

        $coupon = null;

        if ($couponCode !== null) {
            $coupon = $this->validateCoupon($couponCode);
        }

        if ($existing !== null) {
            $this->subscriptions->update((int) $existing['id'], ['status' => 'canceled', 'canceled_at' => $now->format('Y-m-d H:i:s')]);
        }

        $subscriptionId = (int) $this->subscriptions->create([
            'uuid' => str_uuid4(),
            'user_id' => $userId,
            'plan_id' => $plan->id,
            'status' => $useTrial ? 'trialing' : 'active',
            'billing_cycle' => $billingCycle,
            'provider' => $provider->providerName(),
            'provider_customer_id' => $customerId,
            'current_period_start' => $now->format('Y-m-d H:i:s'),
            'current_period_end' => $periodEnd->format('Y-m-d H:i:s'),
            'trial_ends_at' => $useTrial ? $periodEnd->format('Y-m-d H:i:s') : null,
            'cancel_at_period_end' => 0,
            'coupon_id' => $coupon['id'] ?? null,
        ]);

        if ($coupon !== null) {
            $this->coupons->incrementRedemptions((int) $coupon['id']);
            $this->coupons->recordRedemption((int) $coupon['id'], $userId, $subscriptionId);
        }

        if (!$useTrial && $plan->monthlyPrice > 0 || (!$useTrial && $plan->yearlyPrice > 0)) {
            $this->invoiceService->generateForSubscription($subscriptionId, $userId, $plan, $billingCycle, $coupon);
        }

        $this->notifications->notify($userId, 'subscription.created', 'Subscription activated', "You're now subscribed to the {$plan->name} plan.");

        $this->webhooks->dispatch('subscription.created', [
            'user_id' => $userRow['uuid'],
            'plan' => $plan->slug,
            'billing_cycle' => $billingCycle,
        ]);

        return Subscription::fromArray($this->subscriptions->find($subscriptionId));
    }

    public function cancel(int $userId, string $subscriptionUuid, bool $atPeriodEnd = true): Subscription
    {
        $row = $this->subscriptions->findByUuidForUser($subscriptionUuid, $userId);

        if ($row === null) {
            throw new NotFoundException('Subscription not found.');
        }

        $subscription = Subscription::fromArray($row);
        $provider = $this->paymentProviders->provider();
        $provider->cancelSubscription($subscription, $atPeriodEnd);

        $updates = $atPeriodEnd
            ? ['cancel_at_period_end' => 1]
            : ['status' => 'canceled', 'canceled_at' => now_utc()->format('Y-m-d H:i:s')];

        $this->subscriptions->update($subscription->id, $updates);

        $this->notifications->notify(
            $userId,
            'subscription.updated',
            'Subscription canceled',
            $atPeriodEnd
                ? 'Your subscription will end at the close of the current billing period.'
                : 'Your subscription has been canceled immediately.'
        );

        $this->webhooks->dispatch('subscription.updated', [
            'subscription_id' => $subscription->uuid,
            'status' => $atPeriodEnd ? 'canceling' : 'canceled',
        ]);

        return Subscription::fromArray($this->subscriptions->find($subscription->id));
    }

    /**
     * Renews every subscription whose current period has ended.
     * Intended to run from a daily cron job (bin/process-billing-cycle.php).
     *
     * @return array{renewed: int, canceled: int, past_due: int}
     */
    public function processRenewals(): array
    {
        $now = now_utc();
        $due = $this->subscriptions->dueForRenewal($now->format('Y-m-d H:i:s'));
        $renewed = 0;

        foreach ($due as $row) {
            $subscription = Subscription::fromArray($row);
            $plan = Plan::fromArray($this->plans->find($subscription->planId));

            $newPeriodEnd = $this->periodEndFor($now, $subscription->billingCycle);
            $this->subscriptions->update($subscription->id, [
                'current_period_start' => $now->format('Y-m-d H:i:s'),
                'current_period_end' => $newPeriodEnd->format('Y-m-d H:i:s'),
            ]);

            if ($plan->monthlyPrice > 0 || $plan->yearlyPrice > 0) {
                $this->invoiceService->generateForSubscription($subscription->id, $subscription->userId, $plan, $subscription->billingCycle, null);
            }

            $this->notifications->notify($subscription->userId, 'subscription.renewed', 'Subscription renewed', "Your {$plan->name} plan has renewed for another {$subscription->billingCycle} period.");
            $renewed++;
        }

        $canceledAtPeriodEnd = $this->cancelSubscriptionsAtPeriodEnd($now);
        $pastDue = $this->expireOverdueGracePeriods($now);

        return ['renewed' => $renewed, 'canceled' => $canceledAtPeriodEnd, 'past_due' => $pastDue];
    }

    /**
     * Marks trials that have ended as past_due (starting their grace
     * period) rather than immediately cutting access — intended to
     * run from the same daily cron as processRenewals().
     */
    public function processExpiredTrials(): int
    {
        $now = now_utc();
        $expired = $this->subscriptions->expiringTrials($now->format('Y-m-d H:i:s'));
        $graceDays = (int) config('billing.grace_period_days', 7);

        foreach ($expired as $row) {
            $subscription = Subscription::fromArray($row);
            $graceEnd = $now->modify("+{$graceDays} days");

            $this->subscriptions->update($subscription->id, [
                'status' => 'past_due',
                'grace_period_ends_at' => $graceEnd->format('Y-m-d H:i:s'),
            ]);

            $this->notifications->notify(
                $subscription->userId,
                'subscription.trial_ended',
                'Your trial has ended',
                "Your trial period has ended. You have {$graceDays} days to add a payment method before your account is downgraded."
            );
        }

        return count($expired);
    }

    private function cancelSubscriptionsAtPeriodEnd(DateTimeImmutable $now): int
    {
        $due = $this->subscriptions->dueForRenewal($now->format('Y-m-d H:i:s'));
        $count = 0;

        foreach ($due as $row) {
            if (!(bool) $row['cancel_at_period_end']) {
                continue;
            }

            $this->subscriptions->update((int) $row['id'], [
                'status' => 'canceled',
                'canceled_at' => $now->format('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        return $count;
    }

    private function expireOverdueGracePeriods(DateTimeImmutable $now): int
    {
        $expired = $this->subscriptions->expiredGracePeriods($now->format('Y-m-d H:i:s'));

        foreach ($expired as $row) {
            $this->subscriptions->update((int) $row['id'], ['status' => 'expired']);
            $this->notifications->notify(
                (int) $row['user_id'],
                'subscription.expired',
                'Subscription expired',
                'Your subscription has expired due to a lapsed grace period. Renew to restore full access.'
            );
        }

        return count($expired);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCoupon(string $code): array
    {
        $coupon = $this->coupons->findByCode($code);

        if ($coupon === null || !(bool) $coupon['is_active']) {
            throw new ValidationException(['coupon' => ['This coupon code is invalid.']]);
        }

        if ($coupon['valid_from'] !== null && strtotime($coupon['valid_from']) > time()) {
            throw new ValidationException(['coupon' => ['This coupon is not yet valid.']]);
        }

        if ($coupon['valid_until'] !== null && strtotime($coupon['valid_until']) < time()) {
            throw new ValidationException(['coupon' => ['This coupon has expired.']]);
        }

        if ($coupon['max_redemptions'] !== null && (int) $coupon['times_redeemed'] >= (int) $coupon['max_redemptions']) {
            throw new ValidationException(['coupon' => ['This coupon has reached its redemption limit.']]);
        }

        return $coupon;
    }

    private function periodEndFor(DateTimeImmutable $start, string $billingCycle): DateTimeImmutable
    {
        return $billingCycle === 'yearly' ? $start->modify('+1 year') : $start->modify('+1 month');
    }
}
