<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Conversation;

final class ConversationResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Conversation $conversation): array
    {
        return $conversation->toPublicArray();
    }

    /**
     * @param list<Conversation> $conversations
     * @return list<array<string, mixed>>
     */
    public static function collection(array $conversations): array
    {
        return array_map(self::make(...), $conversations);
    }
}
