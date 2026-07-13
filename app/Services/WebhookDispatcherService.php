<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Http\ExternalHttpClient;
use App\Core\Logging\LoggerFactory;
use App\Core\Security\SsrfGuard;
use App\Exceptions\ValidationException;
use App\Repositories\WebhookLogRepository;
use App\Repositories\WebhookRepository;
use Throwable;

/**
 * Fires registered webhooks when a platform event occurs. Delivery is
 * attempted synchronously, inline with the triggering request, with a
 * short timeout — a slow or dead customer endpoint must never block
 * the actual platform action (bot creation, chat completion, etc.)
 * for more than a second or two. Failed deliveries are logged and
 * retried by `bin/process-webhook-retries.php` (cron), up to 5
 * attempts total.
 *
 * Every event listed in the Phase 5 spec is supported:
 * bot.created, bot.deleted, chat.started, chat.completed,
 * lead.created, subscription.created, subscription.updated,
 * user.created, knowledge.uploaded.
 */
final class WebhookDispatcherService
{
    private const DELIVERY_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly WebhookRepository $webhooks,
        private readonly WebhookLogRepository $webhookLogs,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function dispatch(string $event, array $payload): void
    {
        $subscribers = $this->webhooks->activeSubscribersFor($event);

        foreach ($subscribers as $webhook) {
            $this->deliver($webhook, $event, $payload);
        }
    }

    /**
     * @param array<string, mixed> $webhook
     * @param array<string, mixed> $payload
     */
    private function deliver(array $webhook, string $event, array $payload): void
    {
        $body = [
            'event' => $event,
            'timestamp' => now_utc()->format(DATE_ATOM),
            'data' => $payload,
        ];

        $encoded = (string) json_encode($body, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $encoded, $webhook['secret']);

        $logId = (int) $this->webhookLogs->create([
            'uuid' => str_uuid4(),
            'webhook_id' => $webhook['id'],
            'event' => $event,
            'payload' => $encoded,
            'attempt' => 1,
            'status' => 'pending',
            'created_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);

        $this->attemptDelivery($logId, (int) $webhook['id'], $webhook['url'], $encoded, $signature);
    }

    public function retry(array $log, string $url, string $secret): void
    {
        $signature = hash_hmac('sha256', $log['payload'], $secret);
        $this->webhookLogs->incrementAttempt((int) $log['id']);
        $this->attemptDelivery((int) $log['id'], (int) $log['webhook_id'], $url, $log['payload'], $signature);
    }

    private function attemptDelivery(int $logId, int $webhookId, string $url, string $encodedPayload, string $signature): void
    {
        try {
            SsrfGuard::assertSafeUrl($url);
        } catch (ValidationException $e) {
            $this->webhookLogs->markResult($logId, 'failed', null, 'Delivery blocked: ' . $e->getMessage());

            return;
        }

        try {
            $client = new ExternalHttpClient(timeoutSeconds: self::DELIVERY_TIMEOUT_SECONDS, maxAttempts: 1);
            $response = $client->request('POST', $url, [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
            ], $encodedPayload);

            $success = $response['status'] >= 200 && $response['status'] < 300;
            $this->webhookLogs->markResult($logId, $success ? 'success' : 'failed', $response['status'], $response['body']);
            $this->webhooks->touchLastTriggered($webhookId);
        } catch (Throwable $e) {
            $this->webhookLogs->markResult($logId, 'failed', null, $e->getMessage());

            LoggerFactory::channel('system')->warning('Webhook delivery failed.', [
                'webhook_id' => $webhookId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
