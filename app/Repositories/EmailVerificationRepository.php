<?php

declare(strict_types=1);

namespace App\Repositories;

final class EmailVerificationRepository extends BaseRepository
{
    protected string $table = 'email_verifications';
    protected bool $usesSoftDeletes = false;

    public function findValidByTokenHash(string $tokenHash): ?array
    {
        return $this->query()
            ->where('token_hash', '=', $tokenHash)
            ->whereNull('used_at')
            ->whereRaw('expires_at > NOW()')
            ->first();
    }

    public function invalidateForUser(int $userId): void
    {
        $this->query()->where('user_id', '=', $userId)->whereNull('used_at')
            ->update(['used_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function markUsed(int $id): void
    {
        $this->query()->where('id', '=', $id)
            ->update(['used_at' => now_utc()->format('Y-m-d H:i:s')]);
    }
}
