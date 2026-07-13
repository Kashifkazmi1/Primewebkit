<?php

declare(strict_types=1);

namespace App\Repositories;

final class TransactionRepository extends BaseRepository
{
    protected string $table = 'transactions';
    protected bool $usesSoftDeletes = false;

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        return $this->query()->where('user_id', '=', $userId)->orderBy('created_at', 'DESC')->paginate($page, $perPage);
    }
}
