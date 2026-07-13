<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Repositories\NotificationRepository;
use App\Services\Notifications\EmailNotificationChannel;
use App\Services\Notifications\InAppNotificationChannel;
use App\Core\Logging\LoggerFactory;
use Throwable;

/**
 * Sends a notification through every registered channel. In-app is
 * always delivered (cheap, always relevant for the dashboard bell
 * icon); email is sent only for the notification types configured in
 * config('notifications.email_types') to avoid spamming users for
 * low-importance events. See NotificationChannelInterface for how a
 * push channel would be added later.
 */
final class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly InAppNotificationChannel $inApp,
        private readonly EmailNotificationChannel $email,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function notify(int $userId, string $type, string $title, string $body, array $data = []): void
    {
        try {
            $this->inApp->send($userId, $type, $title, $body, $data);
        } catch (Throwable $e) {
            LoggerFactory::channel('system')->error('Failed to deliver in-app notification.', ['error' => $e->getMessage(), 'type' => $type]);
        }

        $emailTypes = (array) config('notifications.email_types', []);

        if (in_array($type, $emailTypes, true)) {
            try {
                $this->email->send($userId, $type, $title, $body, $data);
            } catch (Throwable $e) {
                LoggerFactory::channel('system')->error('Failed to deliver email notification.', ['error' => $e->getMessage(), 'type' => $type]);
            }
        }
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        return $this->notifications->paginateForUser($userId, $page, $perPage);
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->countUnreadForUser($userId);
    }

    public function markRead(int $userId, string $uuid): void
    {
        $notificationRow = $this->findOwnedByUuid($uuid, $userId);
        $this->notifications->markRead((int) $notificationRow['id'], $userId);
    }

    public function markAllRead(int $userId): void
    {
        $this->notifications->markAllRead($userId);
    }

    /**
     * @return array<string, mixed>
     */
    private function findOwnedByUuid(string $uuid, int $userId): array
    {
        $row = \App\Core\Database\QueryBuilder::table('notifications')
            ->withoutSoftDeletes()
            ->where('uuid', '=', $uuid)
            ->where('user_id', '=', $userId)
            ->first();

        if ($row === null) {
            throw new NotFoundException('Notification not found.');
        }

        return $row;
    }
}
