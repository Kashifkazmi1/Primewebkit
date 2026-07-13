<?php

declare(strict_types=1);

namespace App\Repositories;

final class ApiKeyRepository extends BaseRepository
{
    protected string $table = 'api_keys';
    protected bool $usesSoftDeletes = false;

    public function findByHash(string $keyHash): ?array
    {
        return $this->query()
            ->where('key_hash', '=', $keyHash)
            ->whereNull('revoked_at')
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(int $userId): array
    {
        return $this->query()->where('user_id', '=', $userId)->orderBy('created_at', 'DESC')->get();
    }

    public function findByUuidForUser(string $uuid, int $userId): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->where('user_id', '=', $userId)->first();
    }

    public function revoke(int $id): void
    {
        $this->update($id, ['revoked_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function touchLastUsed(int $id): void
    {
        $this->update($id, ['last_used_at' => now_utc()->format('Y-m-d H:i:s')]);
    }
}
