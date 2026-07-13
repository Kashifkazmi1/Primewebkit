<?php

declare(strict_types=1);

namespace App\Core\Security;

/**
 * Wraps PHP's native password hashing so the algorithm/cost can be
 * changed in one place. Uses Argon2id when the extension is
 * available (most modern PHP builds), falling back to bcrypt
 * otherwise (some restricted shared-hosting PHP builds disable
 * Argon2).
 */
final class PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;

        return password_hash($plainPassword, $algo);
    }

    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;

        return password_needs_rehash($hashedPassword, $algo);
    }
}
