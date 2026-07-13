<?php

declare(strict_types=1);

namespace App\Repositories;

final class BotRepository extends BaseRepository
{
    protected string $table = 'bots';

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
    public function paginateForUser(int $userId, int $page = 1, int $perPage = 15): array
    {
        return $this->query()
            ->where('user_id', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->paginate($page, $perPage);
    }

    public function belongsToUser(int $botId, int $userId): bool
    {
        return $this->query()->where('id', '=', $botId)->where('user_id', '=', $userId)->exists();
    }

    public function countForUser(int $userId): int
    {
        return $this->query()->where('user_id', '=', $userId)->count();
    }
}
