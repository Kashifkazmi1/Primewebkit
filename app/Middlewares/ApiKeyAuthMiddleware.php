<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\AuthenticationException;
use App\Repositories\UserRepository;
use App\Services\ApiKeyService;
use App\Models\User;
use Closure;

/**
 * Alternative to JwtAuthMiddleware for programmatic/API-key access
 * (e.g. server-to-server integrations that embed a long-lived key
 * rather than performing a login flow). Reads `X-API-Key`.
 */
final class ApiKeyAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ApiKeyService $apiKeys,
        private readonly UserRepository $users,
    ) {
    }

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $key = $request->header('x-api-key');

        if (!is_string($key) || $key === '') {
            throw new AuthenticationException('An API key must be provided via the X-API-Key header.');
        }

        $userId = $this->apiKeys->resolveUserIdFromKey($key);

        if ($userId === null) {
            throw new AuthenticationException('The provided API key is invalid, expired, or has been revoked.', 'API_KEY_INVALID');
        }

        $userRow = $this->users->findWithRole($userId);

        if ($userRow === null) {
            throw new AuthenticationException('The account for this API key no longer exists.', 'USER_NOT_FOUND');
        }

        $user = User::fromArray($userRow);

        if ($user->isSuspended()) {
            throw new AuthenticationException('This account has been suspended.', 'ACCOUNT_SUSPENDED');
        }

        $request->setAttribute('user', $user);
        $request->setAttribute('auth_method', 'api_key');

        return $next($request);
    }
}
