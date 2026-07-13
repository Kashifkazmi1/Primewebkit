<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Conversation;
use App\Models\Message;

/**
 * Exports a conversation's full message history as JSON or Markdown
 * for a bot owner to download from the dashboard.
 */
final class ConversationExportService
{
    /**
     * @param list<Message> $messages
     */
    public function toJson(Conversation $conversation, array $messages): string
    {
        $payload = [
            'conversation' => $conversation->toPublicArray(),
            'messages' => array_map(
                fn (Message $m) => ['role' => $m->role, 'content' => $m->content, 'created_at' => $m->createdAt],
                $messages
            ),
        ];

        return (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param list<Message> $messages
     */
    public function toMarkdown(Conversation $conversation, array $messages): string
    {
        $lines = [
            "# Conversation " . ($conversation->title ?? $conversation->uuid),
            '',
            "- Started: {$conversation->startedAt}",
            "- Status: {$conversation->status}",
            "- Messages: {$conversation->messageCount}",
            '',
            '---',
            '',
        ];

        foreach ($messages as $message) {
            $speaker = match ($message->role) {
                'user' => 'Visitor',
                'assistant' => 'Assistant',
                default => 'System',
            };

            $lines[] = "**{$speaker}** _{$message->createdAt}_";
            $lines[] = '';
            $lines[] = $message->content;
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
