<?php

declare(strict_types=1);

namespace App\Repositories;

final class VisitorRepository extends BaseRepository
{
    protected string $table = 'visitors';
    protected bool $usesSoftDeletes = false;

    public function findByFingerprint(int $botId, string $fingerprint): ?array
    {
        return $this->query()->where('bot_id', '=', $botId)->where('fingerprint', '=', $fingerprint)->first();
    }

    public function touch(int $id): void
    {
        $this->update($id, ['last_seen_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function countForBot(int $botId): int
    {
        return $this->query()->where('bot_id', '=', $botId)->count();
    }
}
