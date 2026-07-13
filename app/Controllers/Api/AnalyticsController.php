<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\BotService;

final class AnalyticsController
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly BotService $bots,
    ) {
    }

    public function forBot(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $bot = $this->bots->getForUser($uuid, $user->id);

        $groupBy = (string) $request->query('group_by', 'day');
        $limit = min(365, max(1, (int) $request->query('limit', 30)));

        return JsonResponse::success($this->analytics->forBot($bot->id, $groupBy, $limit), 'Analytics retrieved successfully.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
