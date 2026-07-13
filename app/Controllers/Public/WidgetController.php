<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\StreamedResponse;
use App\DTO\AI\StreamChunk;
use App\Models\Bot;
use App\Models\Conversation;
use App\Requests\Conversation\SendMessageRequest;
use App\Requests\Lead\CaptureLeadRequest;
use App\Resources\ConversationResource;
use App\Resources\MessageResource;
use App\Services\AI\ChatOrchestratorService;
use App\Services\AI\ConversationTitleService;
use App\Services\AI\SuggestedQuestionsService;
use App\Services\BotService;
use App\Services\ConversationService;
use App\Services\LeadService;
use App\Services\VisitorService;
use App\Services\WidgetService;
use Throwable;

/**
 * Public, unauthenticated endpoints consumed by the embedded chat
 * widget script running on a customer's website. Access is restricted
 * per-bot by WidgetOriginMiddleware (allowed_domains), not by user
 * login — the visitor is anonymous from the platform's point of view.
 */
final class WidgetController
{
    public function __construct(
        private readonly BotService $bots,
        private readonly WidgetService $widgets,
        private readonly ConversationService $conversations,
        private readonly VisitorService $visitors,
        private readonly LeadService $leads,
        private readonly ChatOrchestratorService $orchestrator,
        private readonly SuggestedQuestionsService $suggestedQuestions,
        private readonly ConversationTitleService $titles,
    ) {
    }

    public function config(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->findPublicByUuid($botUuid);
        $widget = $this->widgets->getPublicByBotUuid($botUuid);

        return JsonResponse::success([
            'bot' => $bot->toWidgetArray(),
            'widget' => $widget->toPublicArray(),
        ], 'Widget configuration retrieved successfully.');
    }

    public function sendMessage(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->findPublicByUuid($botUuid);
        $data = SendMessageRequest::validate($request);

        $visitorId = $this->visitors->findOrCreate($bot->id, $data['fingerprint'], $request->ip(), $request->userAgent());
        $conversation = $this->conversations->startOrResume($bot->id, $data['session_id'], $visitorId);

        $result = $this->orchestrator->handleUserMessage($bot, $conversation->id, $data['message']);

        $refreshedConversation = $this->conversations->getForBot($conversation->uuid, $bot->id);
        $this->maybeGenerateTitle($bot, $refreshedConversation);

        return JsonResponse::created([
            'conversation' => ConversationResource::make($refreshedConversation),
            'message' => MessageResource::make($result['assistantMessage']),
            'user_message' => MessageResource::make($result['userMessage']),
        ], 'Message received.');
    }

    /**
     * Server-Sent Events streaming variant of sendMessage(). The
     * client should connect expecting `Content-Type: text/event-stream`
     * and process `data:` frames as they arrive; a final frame with
     * `"done": true` signals the stream is complete.
     */
    public function sendMessageStream(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->findPublicByUuid($botUuid);
        $data = SendMessageRequest::validate($request);

        $visitorId = $this->visitors->findOrCreate($bot->id, $data['fingerprint'], $request->ip(), $request->userAgent());
        $conversation = $this->conversations->startOrResume($bot->id, $data['session_id'], $visitorId);

        return new StreamedResponse(function () use ($bot, $conversation, $data) {
            try {
                $this->orchestrator->handleUserMessageStream(
                    $bot,
                    $conversation->id,
                    $data['message'],
                    function (StreamChunk $chunk) {
                        if (!$chunk->isFinal) {
                            StreamedResponse::writeEvent(json_encode(['delta' => $chunk->delta, 'done' => false]));
                        } else {
                            StreamedResponse::writeEvent(json_encode([
                                'done' => true,
                                'finish_reason' => $chunk->finishReason,
                                'total_tokens' => $chunk->totalTokens,
                            ]));
                        }
                    },
                    fn () => connection_aborted() === 1
                );

                $refreshedConversation = $this->conversations->getForBot($conversation->uuid, $bot->id);
                $this->maybeGenerateTitle($bot, $refreshedConversation);
            } catch (Throwable $e) {
                $message = 'An error occurred while generating the response. Please try again.';

                if ($e instanceof \App\Exceptions\ExternalServiceException) {
                    $message = str_contains($e->getMessage(), 'SAFETY_BLOCKED')
                        ? 'That message was blocked by the content safety filter. Please rephrase and try again.'
                        : 'The assistant is busy right now — please try again in a moment.';
                }

                StreamedResponse::writeEvent(json_encode([
                    'done' => true,
                    'error' => true,
                    'message' => $message,
                ]));
            }
        });
    }

    public function regenerate(Request $request, string $botUuid, string $conversationUuid): Response
    {
        $bot = $this->bots->findPublicByUuid($botUuid);
        $conversation = $this->conversations->getForBot($conversationUuid, $bot->id);

        $result = $this->orchestrator->regenerate($bot, $conversation->id);

        return JsonResponse::success([
            'message' => MessageResource::make($result['assistantMessage']),
        ], 'Response regenerated successfully.');
    }

    public function stopGeneration(Request $request, string $botUuid, string $conversationUuid): Response
    {
        $bot = $this->bots->findPublicByUuid($botUuid);
        $conversation = $this->conversations->getForBot($conversationUuid, $bot->id);

        $this->conversations->requestCancellation($conversation->id);

        return JsonResponse::success(null, 'Stop request received.');
    }

    public function suggestedQuestions(Request $request, string $botUuid, string $conversationUuid): Response
    {
        $bot = $this->bots->findPublicByUuid($botUuid);
        $conversation = $this->conversations->getForBot($conversationUuid, $bot->id);
        $history = $this->conversations->recentContext($conversation->id, 10);

        return JsonResponse::success(
            ['questions' => $this->suggestedQuestions->suggest($bot, $history)],
            'Suggested questions generated successfully.'
        );
    }

    public function rateConversation(Request $request, string $botUuid, string $conversationUuid): Response
    {
        $bot = $this->bots->findPublicByUuid($botUuid);
        $data = \App\Requests\Conversation\RateConversationRequest::validate($request);

        $this->conversations->rate($conversationUuid, $bot->id, (int) $data['rating'], $data['comment'] ?? null);

        return JsonResponse::success(null, 'Thank you for your feedback!');
    }

    public function captureLead(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->findPublicByUuid($botUuid);
        $data = CaptureLeadRequest::validate($request);

        $conversation = $this->conversations->startOrResume($bot->id, $data['session_id'], null);

        $lead = $this->leads->capture($bot->id, $conversation->id, $data);

        return JsonResponse::created([
            'id' => $lead['uuid'],
            'name' => $lead['name'],
            'email' => $lead['email'],
            'phone' => $lead['phone'],
        ], 'Thank you! Your information has been received.');
    }

    private function maybeGenerateTitle(Bot $bot, Conversation $conversation): void
    {
        if ($conversation->title !== null || $conversation->messageCount < 2) {
            return;
        }

        $history = $this->conversations->history($conversation->id, 1);

        if (empty($history)) {
            return;
        }

        $this->titles->generateAndStore($bot, $conversation->id, $history[0]->content);
    }
}
