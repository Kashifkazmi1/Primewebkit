<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Message;

final class MessageResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Message $message): array
    {
        return $message->toPublicArray();
    }

    /**
     * @param list<Message> $messages
     * @return list<array<string, mixed>>
     */
    public static function collection(array $messages): array
    {
        return array_map(self::make(...), $messages);
    }
}
