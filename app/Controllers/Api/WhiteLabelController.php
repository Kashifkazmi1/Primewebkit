<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Requests\WhiteLabel\UpdateWhiteLabelRequest;
use App\Services\WhiteLabelService;

final class WhiteLabelController
{
    public function __construct(private readonly WhiteLabelService $whiteLabel)
    {
    }

    public function show(Request $request): Response
    {
        $user = $this->currentUser($request);

        return JsonResponse::success($this->whiteLabel->getFor($user->id), 'White-label settings retrieved successfully.');
    }

    public function update(Request $request): Response
    {
        $user = $this->currentUser($request);
        $data = UpdateWhiteLabelRequest::validate($request);

        $result = $this->whiteLabel->updateFor($user->id, $data);

        return JsonResponse::success($result, 'White-label settings updated successfully.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
