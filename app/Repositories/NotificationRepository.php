<?php

declare(strict_types=1);

namespace App\Repositories;

final class NotificationRepository extends BaseRepository
{
    protected string $table = 'notifications';
    protected bool $usesSoftDeletes = false;

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        return $this->query()->where('user_id', '=', $userId)->orderBy('created_at', 'DESC')->paginate($page, $perPage);
    }

    public function countUnreadForUser(int $userId): int
    {
        return $this->query()->where('user_id', '=', $userId)->whereNull('read_at')->count();
    }

    public function markRead(int $id, int $userId): void
    {
        $this->query()->where('id', '=', $id)->where('user_id', '=', $userId)->update(['read_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function markAllRead(int $userId): void
    {
        $this->query()->where('user_id', '=', $userId)->whereNull('read_at')->update(['read_at' => now_utc()->format('Y-m-d H:i:s')]);
    }
}
