<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\Invoice;
use App\Models\Plan;
use App\Repositories\InvoiceRepository;

final class InvoiceService
{
    public function __construct(private readonly InvoiceRepository $invoices)
    {
    }

    /**
     * @param array<string, mixed>|null $coupon
     */
    public function generateForSubscription(int $subscriptionId, int $userId, Plan $plan, string $billingCycle, ?array $coupon): Invoice
    {
        $subtotal = $billingCycle === 'yearly' ? $plan->yearlyPrice : $plan->monthlyPrice;
        $discount = 0.0;

        if ($coupon !== null) {
            $discount = $coupon['type'] === 'percent'
                ? round($subtotal * ((float) $coupon['value'] / 100), 2)
                : min($subtotal, (float) $coupon['value']);
        }

        $total = max(0, round($subtotal - $discount, 2));

        $id = (int) $this->invoices->create([
            'uuid' => str_uuid4(),
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'invoice_number' => $this->invoices->nextInvoiceNumber(),
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => 0,
            'total' => $total,
            'currency' => $plan->currency,
            'status' => $total > 0 ? 'open' : 'paid',
            'due_date' => now_utc()->modify('+7 days')->format('Y-m-d H:i:s'),
            'paid_at' => $total <= 0 ? now_utc()->format('Y-m-d H:i:s') : null,
            'provider' => 'manual',
            'line_items' => json_encode([[
                'description' => "{$plan->name} plan — {$billingCycle} billing",
                'amount' => $subtotal,
            ]]),
        ]);

        return Invoice::fromArray($this->invoices->find($id));
    }

    public function markPaid(string $uuid): Invoice
    {
        $row = $this->invoices->findByUuid($uuid);

        if ($row === null) {
            throw new NotFoundException('Invoice not found.');
        }

        $this->invoices->update((int) $row['id'], ['status' => 'paid', 'paid_at' => now_utc()->format('Y-m-d H:i:s')]);

        return Invoice::fromArray($this->invoices->find((int) $row['id']));
    }

    public function void(string $uuid): Invoice
    {
        $row = $this->invoices->findByUuid($uuid);

        if ($row === null) {
            throw new NotFoundException('Invoice not found.');
        }

        $this->invoices->update((int) $row['id'], ['status' => 'void']);

        return Invoice::fromArray($this->invoices->find((int) $row['id']));
    }

    public function getForUser(string $uuid, int $userId): Invoice
    {
        $row = $this->invoices->findByUuidForUser($uuid, $userId);

        if ($row === null) {
            throw new NotFoundException('Invoice not found.');
        }

        return Invoice::fromArray($row);
    }

    /**
     * @return array{data: list<Invoice>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        $result = $this->invoices->paginateForUser($userId, $page, $perPage);
        $result['data'] = array_map(Invoice::fromArray(...), $result['data']);

        return $result;
    }

    /**
     * @return array{data: list<Invoice>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginatePending(int $page, int $perPage): array
    {
        $result = $this->invoices->paginatePending($page, $perPage);
        $result['data'] = array_map(Invoice::fromArray(...), $result['data']);

        return $result;
    }
}
