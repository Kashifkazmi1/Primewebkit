<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database\QueryBuilder;

final class PermissionRepository extends BaseRepository
{
    protected string $table = 'permissions';
    protected bool $usesSoftDeletes = false;

    public function findBySlug(string $slug): ?array
    {
        return $this->query()->where('slug', '=', $slug)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forRole(int $roleId): array
    {
        return QueryBuilder::table('permissions')
            ->withoutSoftDeletes()
            ->select(['permissions.*'])
            ->join('role_permission', 'role_permission.permission_id', '=', 'permissions.id')
            ->where('role_permission.role_id', '=', $roleId)
            ->get();
    }
}
