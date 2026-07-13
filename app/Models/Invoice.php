<?php

declare(strict_types=1);

namespace App\Models;

final class Invoice
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $userId,
        public readonly ?int $subscriptionId,
        public readonly string $invoiceNumber,
        public readonly float $subtotal,
        public readonly float $discountAmount,
        public readonly float $taxAmount,
        public readonly float $total,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $dueDate,
        public readonly ?string $paidAt,
        public readonly string $provider,
        public readonly array $lineItems,
        public readonly string $createdAt,
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
            subscriptionId: isset($row['subscription_id']) ? (int) $row['subscription_id'] : null,
            invoiceNumber: (string) $row['invoice_number'],
            subtotal: (float) $row['subtotal'],
            discountAmount: (float) $row['discount_amount'],
            taxAmount: (float) $row['tax_amount'],
            total: (float) $row['total'],
            currency: (string) $row['currency'],
            status: (string) $row['status'],
            dueDate: $row['due_date'] ?? null,
            paidAt: $row['paid_at'] ?? null,
            provider: (string) $row['provider'],
            lineItems: !empty($row['line_items']) ? (json_decode((string) $row['line_items'], true) ?: []) : [],
            createdAt: (string) $row['created_at'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'invoice_number' => $this->invoiceNumber,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discountAmount,
            'tax_amount' => $this->taxAmount,
            'total' => $this->total,
            'currency' => $this->currency,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'paid_at' => $this->paidAt,
            'line_items' => $this->lineItems,
            'created_at' => $this->createdAt,
        ];
    }
}
