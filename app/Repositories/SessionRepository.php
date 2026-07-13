<?php

declare(strict_types=1);

namespace App\Repositories;

final class SessionRepository extends BaseRepository
{
    protected string $table = 'sessions';
    protected bool $usesSoftDeletes = false;

    public function findValidByTokenHash(string $tokenHash): ?array
    {
        return $this->query()
            ->where('refresh_token_hash', '=', $tokenHash)
            ->where('is_revoked', '=', 0)
            ->whereRaw('expires_at > NOW()')
            ->first();
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeForUser(int $userId): array
    {
        return $this->query()
            ->where('user_id', '=', $userId)
            ->where('is_revoked', '=', 0)
            ->whereRaw('expires_at > NOW()')
            ->orderBy('last_used_at', 'DESC')
            ->get();
    }

    public function revoke(int $id): void
    {
        $this->update($id, ['is_revoked' => 1]);
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->query()->where('user_id', '=', $userId)->update(['is_revoked' => 1]);
    }

    public function touchLastUsed(int $id): void
    {
        $this->update($id, ['last_used_at' => now_utc()->format('Y-m-d H:i:s')]);
    }
}
