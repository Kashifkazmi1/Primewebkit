<?php

declare(strict_types=1);

namespace App\Repositories;

final class ActivityLogRepository extends BaseRepository
{
    protected string $table = 'activity_logs';
    protected bool $usesSoftDeletes = false;

    public function record(
        ?int $userId,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $properties = []
    ): void {
        $this->create([
            'user_id' => $userId,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => empty($properties) ? null : json_encode($properties),
            'created_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function forUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        return $this->query()
            ->where('user_id', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->paginate($page, $perPage);
    }
}
