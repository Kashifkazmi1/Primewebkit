<?php

declare(strict_types=1);

namespace App\Repositories;

final class InvoiceRepository extends BaseRepository
{
    protected string $table = 'invoices';
    protected bool $usesSoftDeletes = false;

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    public function findByUuidForUser(string $uuid, int $userId): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->where('user_id', '=', $userId)->first();
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        return $this->query()->where('user_id', '=', $userId)->orderBy('created_at', 'DESC')->paginate($page, $perPage);
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginatePending(int $page, int $perPage): array
    {
        return $this->query()->where('status', '=', 'open')->orderBy('due_date', 'ASC')->paginate($page, $perPage);
    }

    public function nextInvoiceNumber(): string
    {
        $year = date('Y');
        $count = $this->query()->whereRaw('invoice_number LIKE :prefix', ['prefix' => "INV-{$year}-%"])->count();

        return sprintf('INV-%s-%06d', $year, $count + 1);
    }

    public function sumPaidRevenue(?string $fromDate = null, ?string $toDate = null): float
    {
        $query = $this->query()->where('status', '=', 'paid');

        if ($fromDate !== null) {
            $query->where('paid_at', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->where('paid_at', '<=', $toDate);
        }

        $rows = $query->get();

        return round(array_sum(array_column($rows, 'total')), 2);
    }
}
