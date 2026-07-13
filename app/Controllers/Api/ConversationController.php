<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Resources\ConversationResource;
use App\Resources\MessageResource;
use App\Services\AI\ConversationExportService;
use App\Services\BotService;
use App\Services\ConversationService;

final class ConversationController
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly BotService $bots,
        private readonly ConversationExportService $export,
    ) {
    }

    public function index(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->conversations->paginateForBot($bot->id, $page, $perPage);

        return JsonResponse::success(ConversationResource::collection($result['data']), 'Conversations retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function show(Request $request, string $botUuid, string $conversationUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $conversation = $this->conversations->getForBot($conversationUuid, $bot->id);
        $messages = $this->conversations->history($conversation->id);

        return JsonResponse::success([
            'conversation' => ConversationResource::make($conversation),
            'messages' => MessageResource::collection($messages),
        ], 'Conversation retrieved successfully.');
    }

    public function close(Request $request, string $botUuid, string $conversationUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $this->conversations->close($conversationUuid, $bot->id);

        return JsonResponse::success(null, 'Conversation closed successfully.');
    }

    public function export(Request $request, string $botUuid, string $conversationUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $conversation = $this->conversations->getForBot($conversationUuid, $bot->id);
        $messages = $this->conversations->history($conversation->id);

        $format = (string) $request->query('format', 'json');

        if ($format === 'markdown') {
            $content = $this->export->toMarkdown($conversation, $messages);
            $mime = 'text/markdown';
        } else {
            $content = $this->export->toJson($conversation, $messages);
            $mime = 'application/json';
        }

        return JsonResponse::success([
            'format' => $format === 'markdown' ? 'markdown' : 'json',
            'content' => $content,
            'mime_type' => $mime,
            'filename' => "conversation-{$conversation->uuid}." . ($format === 'markdown' ? 'md' : 'json'),
        ], 'Conversation exported successfully.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
