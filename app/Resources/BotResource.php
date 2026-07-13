<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Bot;

final class BotResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Bot $bot): array
    {
        return $bot->toPublicArray();
    }

    /**
     * @param list<Bot> $bots
     * @return list<array<string, mixed>>
     */
    public static function collection(array $bots): array
    {
        return array_map(self::make(...), $bots);
    }
}
