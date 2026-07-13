<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Services\NotificationService;

final class NotificationController
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): Response
    {
        $user = $this->currentUser($request);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->notifications->paginateForUser($user->id, $page, $perPage);

        return JsonResponse::success($result['data'], 'Notifications retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function unreadCount(Request $request): Response
    {
        $user = $this->currentUser($request);

        return JsonResponse::success(['unread_count' => $this->notifications->unreadCount($user->id)], 'Unread count retrieved successfully.');
    }

    public function markRead(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $this->notifications->markRead($user->id, $uuid);

        return JsonResponse::success(null, 'Notification marked as read.');
    }

    public function markAllRead(Request $request): Response
    {
        $user = $this->currentUser($request);
        $this->notifications->markAllRead($user->id);

        return JsonResponse::success(null, 'All notifications marked as read.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
