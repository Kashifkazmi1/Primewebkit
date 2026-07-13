<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Repositories\AIUsageLogRepository;
use App\Services\BotService;

final class AIUsageController
{
    public function __construct(
        private readonly AIUsageLogRepository $usageLogs,
        private readonly BotService $bots,
    ) {
    }

    public function index(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->usageLogs->paginateForBot($bot->id, $page, $perPage);

        return JsonResponse::success($result['data'], 'AI usage logs retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function summary(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);

        $fromDate = $request->query('from');
        $toDate = $request->query('to');

        $summary = $this->usageLogs->summaryForBot($bot->id, is_string($fromDate) ? $fromDate : null, is_string($toDate) ? $toDate : null);

        return JsonResponse::success($summary, 'AI usage summary retrieved successfully.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
