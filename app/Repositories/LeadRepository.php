<?php

declare(strict_types=1);

namespace App\Repositories;

final class LeadRepository extends BaseRepository
{
    protected string $table = 'leads';

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForBot(int $botId, int $page = 1, int $perPage = 20): array
    {
        return $this->query()->where('bot_id', '=', $botId)->orderBy('created_at', 'DESC')->paginate($page, $perPage);
    }

    public function countForBot(int $botId): int
    {
        return $this->query()->where('bot_id', '=', $botId)->count();
    }
}
