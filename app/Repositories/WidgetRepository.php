<?php

declare(strict_types=1);

namespace App\Repositories;

final class WidgetRepository extends BaseRepository
{
    protected string $table = 'widgets';
    protected bool $usesSoftDeletes = false;

    public function findByBotId(int $botId): ?array
    {
        return $this->query()->where('bot_id', '=', $botId)->first();
    }

    public function findByBotUuid(string $botUuid): ?array
    {
        return \App\Core\Database\QueryBuilder::table('widgets')
            ->withoutSoftDeletes()
            ->select(['widgets.*'])
            ->join('bots', 'bots.id', '=', 'widgets.bot_id')
            ->where('bots.uuid', '=', $botUuid)
            ->whereNull('bots.deleted_at')
            ->first();
    }
}
