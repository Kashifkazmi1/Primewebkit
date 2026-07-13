<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * A delivery channel for a notification. `InAppNotificationChannel`
 * and `EmailNotificationChannel` implement this now.
 * `NotificationService` iterates every bound channel for a given
 * notification — adding push means writing a
 * `PushNotificationChannel` class implementing this interface (via a
 * real provider SDK/credentials, e.g. FCM) and registering it in
 * `NotificationService`'s channel list; nothing else changes. Not
 * implemented in this phase since it requires push provider
 * credentials this platform doesn't have configured.
 */
interface NotificationChannelInterface
{
    public function channelName(): string;

    public function send(int $userId, string $type, string $title, string $body, array $data = []): void;
}
