<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database\Connection;
use App\Core\Database\QueryBuilder;

final class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->query()->where('email', '=', $email)->first();
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->query()->where('uuid', '=', $uuid)->first();
    }

    public function emailExists(string $email): bool
    {
        return $this->query()->where('email', '=', $email)->exists();
    }

    /**
     * Fetch a user joined with its role slug — used everywhere the
     * application needs to know the user's role (auth, RBAC checks).
     */
    public function findWithRole(int $id): ?array
    {
        return QueryBuilder::table('users')
            ->select([
                'users.*',
                'roles.slug AS role_slug',
                'roles.name AS role_name',
            ])
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.id', '=', $id)
            ->first();
    }

    public function findByEmailWithRole(string $email): ?array
    {
        return QueryBuilder::table('users')
            ->select([
                'users.*',
                'roles.slug AS role_slug',
                'roles.name AS role_name',
            ])
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.email', '=', $email)
            ->first();
    }

    public function incrementFailedLoginAttempts(int $userId): void
    {
        Connection::get()
            ->prepare('UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = :id')
            ->execute(['id' => $userId]);
    }

    public function resetFailedLoginAttempts(int $userId): void
    {
        $this->update($userId, ['failed_login_attempts' => 0, 'locked_until' => null]);
    }

    public function lockUntil(int $userId, string $until): void
    {
        $this->update($userId, ['locked_until' => $until]);
    }

    public function recordLogin(int $userId, string $ip): void
    {
        $this->update($userId, [
            'last_login_at' => now_utc()->format('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
        ]);
    }

    public function markEmailVerified(int $userId): void
    {
        $this->update($userId, [
            'email_verified_at' => now_utc()->format('Y-m-d H:i:s'),
            'status' => 'active',
        ]);
    }

    public function updatePassword(int $userId, string $hashedPassword): void
    {
        $this->update($userId, ['password' => $hashedPassword]);
    }
}
