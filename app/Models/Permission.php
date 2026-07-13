<?php

declare(strict_types=1);

namespace App\Models;

final class Permission
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $group,
        public readonly ?string $description,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            group: $row['group'] ?? null,
            description: $row['description'] ?? null,
        );
    }
}
