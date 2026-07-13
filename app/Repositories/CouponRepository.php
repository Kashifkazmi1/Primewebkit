<?php

declare(strict_types=1);

namespace App\Repositories;

final class CouponRepository extends BaseRepository
{
    protected string $table = 'coupons';
    protected bool $usesSoftDeletes = false;

    public function findByCode(string $code): ?array
    {
        return $this->query()->where('code', '=', strtoupper($code))->first();
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    public function incrementRedemptions(int $id): void
    {
        \App\Core\Database\Connection::get()
            ->prepare('UPDATE coupons SET times_redeemed = times_redeemed + 1 WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function recordRedemption(int $couponId, int $userId, ?int $subscriptionId): void
    {
        \App\Core\Database\Connection::get()
            ->prepare('INSERT INTO coupon_redemptions (coupon_id, user_id, subscription_id, redeemed_at) VALUES (:coupon_id, :user_id, :subscription_id, NOW())')
            ->execute(['coupon_id' => $couponId, 'user_id' => $userId, 'subscription_id' => $subscriptionId]);
    }
}
