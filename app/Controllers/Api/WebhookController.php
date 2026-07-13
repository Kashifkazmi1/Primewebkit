<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Requests\Webhook\CreateWebhookRequest;
use App\Services\WebhookService;

final class WebhookController
{
    public function __construct(private readonly WebhookService $webhooks)
    {
    }

    public function index(Request $request): Response
    {
        $user = $this->currentUser($request);

        return JsonResponse::success($this->webhooks->listForUser($user->id), 'Webhooks retrieved successfully.');
    }

    public function store(Request $request): Response
    {
        $user = $this->currentUser($request);
        $data = CreateWebhookRequest::validate($request);

        $result = $this->webhooks->register($user->id, $data['url'], $data['events']);

        return JsonResponse::created($result, 'Webhook registered successfully. Save the secret now — it will not be shown again.');
    }

    public function destroy(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $this->webhooks->delete($uuid, $user->id);

        return JsonResponse::success(null, 'Webhook deleted successfully.');
    }

    public function toggle(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $rawValue = $request->input('is_active', true);
        $active = is_bool($rawValue) ? $rawValue : filter_var($rawValue, FILTER_VALIDATE_BOOLEAN);

        $result = $this->webhooks->toggle($uuid, $user->id, $active);

        return JsonResponse::success($result, 'Webhook updated successfully.');
    }

    public function logs(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $result = $this->webhooks->logsFor($uuid, $user->id, $page, $perPage);

        return JsonResponse::success($result['data'], 'Webhook logs retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function events(Request $request): Response
    {
        return JsonResponse::success(['events' => WebhookService::SUPPORTED_EVENTS], 'Supported webhook events retrieved successfully.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
