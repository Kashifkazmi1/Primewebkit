<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Requests\ApiKey\CreateApiKeyRequest;
use App\Services\ApiKeyService;

final class ApiKeyController
{
    public function __construct(private readonly ApiKeyService $apiKeys)
    {
    }

    public function index(Request $request): Response
    {
        $user = $this->currentUser($request);

        return JsonResponse::success($this->apiKeys->listForUser($user->id), 'API keys retrieved successfully.');
    }

    public function store(Request $request): Response
    {
        $user = $this->currentUser($request);
        $data = CreateApiKeyRequest::validate($request);

        $result = $this->apiKeys->create($user->id, $data['name'], $data['expires_at'] ?? null, $data['scopes'] ?? []);

        return JsonResponse::created(
            $result,
            'API key created successfully. Copy it now — it will not be shown again.'
        );
    }

    public function destroy(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $this->apiKeys->revoke($uuid, $user->id);

        return JsonResponse::success(null, 'API key revoked successfully.');
    }

    public function rotate(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $result = $this->apiKeys->rotate($uuid, $user->id);

        return JsonResponse::success($result, 'API key rotated successfully. Save the new key now — it will not be shown again.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
