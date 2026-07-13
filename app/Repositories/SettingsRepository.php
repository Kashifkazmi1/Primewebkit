<?php

declare(strict_types=1);

namespace App\Repositories;

final class SettingsRepository extends BaseRepository
{
    protected string $table = 'settings';
    protected bool $usesSoftDeletes = false;

    public function findByKey(string $key): ?array
    {
        return $this->query()->where('key', '=', $key)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allInGroup(string $group): array
    {
        return $this->query()->where('group', '=', $group)->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->query()->orderBy('group', 'ASC')->get();
    }

    public function upsert(string $key, mixed $value, string $type, string $group): void
    {
        $existing = $this->findByKey($key);
        $stored = $this->encode($value, $type);

        if ($existing === null) {
            $this->create(['key' => $key, 'value' => $stored, 'type' => $type, 'group' => $group]);

            return;
        }

        $this->update((int) $existing['id'], ['value' => $stored, 'type' => $type, 'group' => $group]);
    }

    private function encode(mixed $value, string $type): string
    {
        return match ($type) {
            'json' => (string) json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
