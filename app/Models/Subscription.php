<?php

declare(strict_types=1);

namespace App\Models;

final class Subscription
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $userId,
        public readonly int $planId,
        public readonly string $status,
        public readonly string $billingCycle,
        public readonly string $provider,
        public readonly ?string $providerCustomerId,
        public readonly ?string $providerSubscriptionId,
        public readonly string $currentPeriodStart,
        public readonly string $currentPeriodEnd,
        public readonly ?string $trialEndsAt,
        public readonly ?string $gracePeriodEndsAt,
        public readonly bool $cancelAtPeriodEnd,
        public readonly ?string $canceledAt,
        public readonly ?int $couponId,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            uuid: (string) $row['uuid'],
            userId: (int) $row['user_id'],
            planId: (int) $row['plan_id'],
            status: (string) $row['status'],
            billingCycle: (string) $row['billing_cycle'],
            provider: (string) $row['provider'],
            providerCustomerId: $row['provider_customer_id'] ?? null,
            providerSubscriptionId: $row['provider_subscription_id'] ?? null,
            currentPeriodStart: (string) $row['current_period_start'],
            currentPeriodEnd: (string) $row['current_period_end'],
            trialEndsAt: $row['trial_ends_at'] ?? null,
            gracePeriodEndsAt: $row['grace_period_ends_at'] ?? null,
            cancelAtPeriodEnd: (bool) $row['cancel_at_period_end'],
            canceledAt: $row['canceled_at'] ?? null,
            couponId: isset($row['coupon_id']) ? (int) $row['coupon_id'] : null,
        );
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true);
    }

    public function isInTrial(): bool
    {
        return $this->status === 'trialing' && $this->trialEndsAt !== null && strtotime($this->trialEndsAt) > time();
    }

    public function isInGracePeriod(): bool
    {
        return $this->status === 'past_due' && $this->gracePeriodEndsAt !== null && strtotime($this->gracePeriodEndsAt) > time();
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'status' => $this->status,
            'billing_cycle' => $this->billingCycle,
            'provider' => $this->provider,
            'current_period_start' => $this->currentPeriodStart,
            'current_period_end' => $this->currentPeriodEnd,
            'trial_ends_at' => $this->trialEndsAt,
            'grace_period_ends_at' => $this->gracePeriodEndsAt,
            'cancel_at_period_end' => $this->cancelAtPeriodEnd,
            'canceled_at' => $this->canceledAt,
        ];
    }
}
