<?php

declare(strict_types=1);

namespace App\Repositories;

final class TeamRepository extends BaseRepository
{
    protected string $table = 'teams';

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function ownedBy(int $userId): array
    {
        return $this->query()->where('owner_id', '=', $userId)->get();
    }
}
