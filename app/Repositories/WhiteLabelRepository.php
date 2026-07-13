<?php

declare(strict_types=1);

namespace App\Repositories;

final class WhiteLabelRepository extends BaseRepository
{
    protected string $table = 'white_label_settings';
    protected bool $usesSoftDeletes = false;

    public function findForUser(int $userId): ?array
    {
        return $this->query()->where('user_id', '=', $userId)->first();
    }
}
