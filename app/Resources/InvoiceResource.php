<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Invoice;

final class InvoiceResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Invoice $invoice): array
    {
        return $invoice->toPublicArray();
    }

    /**
     * @param list<Invoice> $invoices
     * @return list<array<string, mixed>>
     */
    public static function collection(array $invoices): array
    {
        return array_map(self::make(...), $invoices);
    }
}
