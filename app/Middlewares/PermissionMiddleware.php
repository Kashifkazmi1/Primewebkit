<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Models\User;
use App\Repositories\PermissionRepository;
use Closure;

/**
 * Fine-grained permission check on top of RoleMiddleware's coarse
 * role check. Usage: `PermissionMiddleware::class . ':users.suspend'`.
 * Super-admins bypass this check entirely (they implicitly have every
 * permission) — everyone else must have their role explicitly granted
 * the named permission via role_permission (seeded in Phase 2).
 * Must run after JwtAuthMiddleware.
 */
final class PermissionMiddleware implements MiddlewareInterface
{
    /**
     * @var array<int, array<string, bool>> per-role permission cache for this request lifecycle
     */
    private static array $cache = [];

    public function __construct(private readonly PermissionRepository $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $requiredPermission = $params[0] ?? null;

        if ($requiredPermission === null) {
            return $next($request);
        }

        /** @var User|null $user */
        $user = $request->getAttribute('user');

        if ($user === null) {
            throw new AuthenticationException('Authentication is required to access this resource.');
        }

        if ($user->roleSlug === 'super-admin') {
            return $next($request);
        }

        if (!$this->roleHasPermission($user->roleId, $requiredPermission)) {
            throw new AuthorizationException("You do not have the required permission [{$requiredPermission}] for this action.");
        }

        return $next($request);
    }

    private function roleHasPermission(int $roleId, string $permissionSlug): bool
    {
        if (!isset(self::$cache[$roleId])) {
            $grants = $this->permissions->forRole($roleId);
            self::$cache[$roleId] = array_fill_keys(array_column($grants, 'slug'), true);
        }

        return self::$cache[$roleId][$permissionSlug] ?? false;
    }
}
