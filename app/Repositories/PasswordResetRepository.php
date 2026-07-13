<?php

declare(strict_types=1);

namespace App\Repositories;

final class PasswordResetRepository extends BaseRepository
{
    protected string $table = 'password_resets';
    protected bool $usesSoftDeletes = false;

    public function findValidByTokenHash(string $tokenHash): ?array
    {
        return $this->query()
            ->where('token_hash', '=', $tokenHash)
            ->whereNull('used_at')
            ->whereRaw('expires_at > NOW()')
            ->first();
    }

    public function invalidateForEmail(string $email): void
    {
        $this->query()->where('email', '=', $email)->whereNull('used_at')
            ->update(['used_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function markUsed(int $id): void
    {
        $this->query()->where('id', '=', $id)
            ->update(['used_at' => now_utc()->format('Y-m-d H:i:s')]);
    }
}
