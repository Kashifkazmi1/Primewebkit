<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Generates cryptographically secure opaque tokens (refresh tokens,
 * password-reset tokens, email-verification tokens) and their storage
 * hashes. Only the hash is ever persisted — the raw token is shown to
 * the client exactly once and can never be recovered from the DB.
 */
final class TokenService
{
    public function generate(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}
