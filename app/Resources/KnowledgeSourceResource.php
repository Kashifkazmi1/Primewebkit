<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\KnowledgeSource;

final class KnowledgeSourceResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(KnowledgeSource $source): array
    {
        return $source->toPublicArray();
    }

    /**
     * @param list<KnowledgeSource> $sources
     * @return list<array<string, mixed>>
     */
    public static function collection(array $sources): array
    {
        return array_map(self::make(...), $sources);
    }
}
