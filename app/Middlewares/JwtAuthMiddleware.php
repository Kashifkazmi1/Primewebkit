<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\AuthenticationException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\JwtService;
use Closure;

/**
 * Verifies the Authorization: Bearer <jwt> header, loads the
 * corresponding user (rejecting suspended accounts), and attaches
 * both the raw claims and a hydrated User model to the request for
 * downstream controllers/middleware to use.
 */
final class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly UserRepository $users,
    ) {
    }

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new AuthenticationException('Authentication token was not provided.');
        }

        $claims = $this->jwt->verify($token);
        $uuid = $claims['sub'] ?? null;

        if (!is_string($uuid)) {
            throw new AuthenticationException('The access token is malformed.', 'TOKEN_MALFORMED');
        }

        $userRow = $this->users->findByUuid($uuid);

        if ($userRow === null) {
            throw new AuthenticationException('The account for this token no longer exists.', 'USER_NOT_FOUND');
        }

        $fullRow = $this->users->findWithRole((int) $userRow['id']);
        $user = User::fromArray($fullRow);

        if ($user->isSuspended()) {
            throw new AuthenticationException('This account has been suspended.', 'ACCOUNT_SUSPENDED');
        }

        $request->setAttribute('jwt_claims', $claims);
        $request->setAttribute('user', $user);

        return $next($request);
    }
}
