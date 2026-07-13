<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Core\Contracts\NotificationChannelInterface;
use App\Repositories\UserRepository;
use App\Services\MailService;

final class EmailNotificationChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly MailService $mail,
        private readonly UserRepository $users,
    ) {
    }

    public function channelName(): string
    {
        return 'email';
    }

    public function send(int $userId, string $type, string $title, string $body, array $data = []): void
    {
        $user = $this->users->find($userId);

        if ($user === null) {
            return;
        }

        $this->mail->send($user['email'], $user['name'], $title, "<p>{$body}</p>");
    }
}
