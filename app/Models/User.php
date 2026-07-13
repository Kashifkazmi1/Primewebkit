<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Read-oriented domain representation of a `users` row. Repositories
 * return raw arrays (see BaseRepository) — Services hydrate a Model
 * when they need typed access and behaviour (isLocked(), etc.)
 * rather than passing raw arrays around the domain layer.
 */
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $roleId,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $emailVerifiedAt,
        public readonly string $password,
        public readonly string $status,
        public readonly ?string $avatarPath,
        public readonly string $timezone,
        public readonly string $locale,
        public readonly int $failedLoginAttempts,
        public readonly ?string $lockedUntil,
        public readonly ?string $lastLoginAt,
        public readonly ?string $lastLoginIp,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $roleSlug = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            uuid: (string) $row['uuid'],
            roleId: (int) $row['role_id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            emailVerifiedAt: $row['email_verified_at'] ?? null,
            password: (string) $row['password'],
            status: (string) $row['status'],
            avatarPath: $row['avatar_path'] ?? null,
            timezone: (string) ($row['timezone'] ?? 'UTC'),
            locale: (string) ($row['locale'] ?? 'en'),
            failedLoginAttempts: (int) ($row['failed_login_attempts'] ?? 0),
            lockedUntil: $row['locked_until'] ?? null,
            lastLoginAt: $row['last_login_at'] ?? null,
            lastLoginIp: $row['last_login_ip'] ?? null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            roleSlug: $row['role_slug'] ?? null,
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && strtotime($this->lockedUntil) > time();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roleSlug === 'super-admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->roleSlug, ['super-admin', 'admin'], true);
    }

    /**
     * Public-safe representation (never includes the password hash).
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->hasVerifiedEmail(),
            'role' => $this->roleSlug,
            'status' => $this->status,
            'avatar_url' => $this->avatarPath,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
        ];
    }
}
