<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Models\User;
use Closure;

/**
 * Restricts a route to one or more role slugs, e.g.:
 *
 *   $router->get('/admin/users', [AdminUserController::class, 'index'])
 *       ->withMiddleware([JwtAuthMiddleware::class, RoleMiddleware::class . ':super-admin,admin']);
 *
 * Must run AFTER JwtAuthMiddleware in the pipeline, since it relies
 * on the `user` request attribute being already populated.
 */
final class RoleMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        /** @var User|null $user */
        $user = $request->getAttribute('user');

        if ($user === null) {
            throw new AuthenticationException('Authentication is required to access this resource.');
        }

        if (!empty($params) && !in_array($user->roleSlug, $params, true)) {
            throw new AuthorizationException('You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
