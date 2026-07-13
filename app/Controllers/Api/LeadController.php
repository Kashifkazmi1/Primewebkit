<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Services\BotService;
use App\Services\LeadService;

final class LeadController
{
    public function __construct(
        private readonly LeadService $leads,
        private readonly BotService $bots,
    ) {
    }

    public function index(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->leads->paginateForBot($bot->id, $page, $perPage);

        return JsonResponse::success($result['data'], 'Leads retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
