<?php

declare(strict_types=1);

namespace App\Repositories;

final class WebhookRepository extends BaseRepository
{
    protected string $table = 'webhooks';
    protected bool $usesSoftDeletes = false;

    public function findByUuidForUser(string $uuid, int $userId): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->where('user_id', '=', $userId)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(int $userId): array
    {
        return $this->query()->where('user_id', '=', $userId)->orderBy('created_at', 'DESC')->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeSubscribersFor(string $event): array
    {
        $rows = $this->query()->where('is_active', '=', 1)->get();

        return array_values(array_filter($rows, function (array $row) use ($event) {
            $events = json_decode((string) $row['events'], true) ?: [];

            return in_array($event, $events, true) || in_array('*', $events, true);
        }));
    }

    public function touchLastTriggered(int $id): void
    {
        $this->update($id, ['last_triggered_at' => now_utc()->format('Y-m-d H:i:s')]);
    }
}
