<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database\QueryBuilder;

/**
 * Base repository providing common CRUD operations. Every concrete
 * repository declares its table name and gets find/create/update/
 * delete/paginate for free, while still being free to add
 * domain-specific query methods of its own.
 */
abstract class BaseRepository
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $usesSoftDeletes = true;

    protected function query(): QueryBuilder
    {
        $query = QueryBuilder::table($this->table);

        return $this->usesSoftDeletes ? $query : $query->withoutSoftDeletes();
    }

    public function find(int|string $id): ?array
    {
        return $this->query()->find($id, $this->primaryKey);
    }

    public function findOrFailBy(string $column, mixed $value): ?array
    {
        return $this->query()->where($column, '=', $value)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->query()->get();
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        return $this->query()->orderBy($this->primaryKey, 'DESC')->paginate($page, $perPage);
    }

    public function create(array $data): string|int
    {
        return $this->query()->insertGetId($data);
    }

    public function update(int|string $id, array $data): int
    {
        return $this->query()->where($this->primaryKey, '=', $id)->update($data);
    }

    public function delete(int|string $id): int
    {
        $query = $this->query()->where($this->primaryKey, '=', $id);

        return $this->usesSoftDeletes ? $query->softDelete() : $query->delete();
    }

    public function forceDelete(int|string $id): int
    {
        return $this->query()->where($this->primaryKey, '=', $id)->withTrashed()->delete();
    }

    public function restore(int|string $id): int
    {
        if (!$this->usesSoftDeletes) {
            return 0;
        }

        return $this->query()->where($this->primaryKey, '=', $id)->onlyTrashed()->restore();
    }
}
