<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Security\SsrfGuard;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\WebhookLogRepository;
use App\Repositories\WebhookRepository;

final class WebhookService
{
    public const SUPPORTED_EVENTS = [
        'bot.created', 'bot.deleted', 'chat.started', 'chat.completed',
        'lead.created', 'subscription.created', 'subscription.updated',
        'user.created', 'knowledge.uploaded',
    ];

    public function __construct(
        private readonly WebhookRepository $webhooks,
        private readonly WebhookLogRepository $webhookLogs,
    ) {
    }

    /**
     * @param list<string> $events
     * @return array<string, mixed>
     */
    public function register(int $userId, string $url, array $events): array
    {
        $invalid = array_diff($events, self::SUPPORTED_EVENTS);

        if (!empty($invalid)) {
            throw new ValidationException(['events' => ['Unsupported event(s): ' . implode(', ', $invalid)]]);
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException(['url' => ['A valid URL is required.']]);
        }

        SsrfGuard::assertSafeUrl($url, 'url');

        $secret = bin2hex(random_bytes(32));

        $id = (int) $this->webhooks->create([
            'uuid' => str_uuid4(),
            'user_id' => $userId,
            'url' => $url,
            'secret' => $secret,
            'events' => json_encode($events),
            'is_active' => 1,
        ]);

        $row = $this->webhooks->find($id);

        // The signing secret is only ever returned here, at creation
        // time — every other read (list, toggle) uses toPublicArray(),
        // which omits it entirely. There is no "reveal secret again"
        // endpoint; losing it means rotating the webhook by deleting
        // and re-registering.
        return [...$this->toPublicArray($row), 'secret' => $secret];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return array_map($this->toPublicArray(...), $this->webhooks->forUser($userId));
    }

    public function delete(string $uuid, int $userId): void
    {
        $row = $this->findOwnedOrFail($uuid, $userId);
        $this->webhooks->delete((int) $row['id']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toggle(string $uuid, int $userId, bool $active): array
    {
        $row = $this->findOwnedOrFail($uuid, $userId);

        $this->webhooks->update((int) $row['id'], ['is_active' => $active ? 1 : 0]);

        return $this->toPublicArray($this->webhooks->find((int) $row['id']));
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function logsFor(string $uuid, int $userId, int $page, int $perPage): array
    {
        $row = $this->findOwnedOrFail($uuid, $userId);

        return $this->webhookLogs->paginateForWebhook((int) $row['id'], $page, $perPage);
    }

    /**
     * @return array<string, mixed>
     */
    private function findOwnedOrFail(string $uuid, int $userId): array
    {
        $row = $this->webhooks->findByUuidForUser($uuid, $userId);

        if ($row === null) {
            throw new NotFoundException('Webhook not found.');
        }

        return $row;
    }

    /**
     * Never exposes the internal auto-increment `id` or the signing
     * `secret` — matches the shape every other resource in this API
     * uses (`uuid` presented as the public `id`).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function toPublicArray(array $row): array
    {
        return [
            'id' => $row['uuid'],
            'url' => $row['url'],
            'events' => json_decode((string) $row['events'], true) ?: [],
            'is_active' => (bool) $row['is_active'],
            'last_triggered_at' => $row['last_triggered_at'] ?? null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
