<?php

declare(strict_types=1);

namespace App\Repositories;

final class RoleRepository extends BaseRepository
{
    protected string $table = 'roles';

    public function findBySlug(string $slug): ?array
    {
        return $this->query()->where('slug', '=', $slug)->first();
    }
}
