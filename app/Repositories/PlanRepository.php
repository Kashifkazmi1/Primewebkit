<?php

declare(strict_types=1);

namespace App\Repositories;

final class PlanRepository extends BaseRepository
{
    protected string $table = 'plans';

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->query()->where('slug', '=', $slug)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allActive(): array
    {
        return $this->query()->where('is_active', '=', 1)->orderBy('sort_order', 'ASC')->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->query()->orderBy('sort_order', 'ASC')->get();
    }
}
