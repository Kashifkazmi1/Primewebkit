<?php

declare(strict_types=1);

namespace App\Models;

final class Team
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $ownerId,
        public readonly string $name,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            uuid: (string) $row['uuid'],
            ownerId: (int) $row['owner_id'],
            name: (string) $row['name'],
            createdAt: (string) $row['created_at'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'created_at' => $this->createdAt,
        ];
    }
}
