<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\WebhookLogRepository;

final class AdminWebhookController
{
    public function __construct(private readonly WebhookLogRepository $webhookLogs)
    {
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->webhookLogs->paginateAll($page, $perPage);

        return JsonResponse::success($result['data'], 'Webhook logs retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }
}
