<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Core\Contracts\NotificationChannelInterface;
use App\Repositories\NotificationRepository;

final class InAppNotificationChannel implements NotificationChannelInterface
{
    public function __construct(private readonly NotificationRepository $notifications)
    {
    }

    public function channelName(): string
    {
        return 'in_app';
    }

    public function send(int $userId, string $type, string $title, string $body, array $data = []): void
    {
        $this->notifications->create([
            'uuid' => str_uuid4(),
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => empty($data) ? null : json_encode($data),
            'channel' => 'in_app',
            'created_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);
    }
}
