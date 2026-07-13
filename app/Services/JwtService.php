<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthenticationException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use UnexpectedValueException;

/**
 * Issues and verifies short-lived JWT access tokens.
 *
 * Refresh tokens are deliberately NOT JWTs — they are opaque random
 * strings stored (hashed) in the `sessions` table, which allows
 * server-side revocation (logout, "log out all devices"). Access
 * tokens are stateless JWTs so most requests never touch the DB for
 * auth.
 */
final class JwtService
{
    private readonly string $secret;
    private readonly string $algo;
    private readonly string $issuer;
    private readonly string $audience;
    private readonly int $accessTtlMinutes;

    public function __construct()
    {
        $this->secret = (string) config('jwt.secret');
        $this->algo = (string) config('jwt.algo', 'HS256');
        $this->issuer = (string) config('jwt.issuer');
        $this->audience = (string) config('jwt.audience');
        $this->accessTtlMinutes = (int) config('jwt.access_ttl_minutes', 15);

        if ($this->secret === '') {
            throw new \RuntimeException('JWT_SECRET is not configured.');
        }
    }

    /**
     * @param array<string, mixed> $claims additional custom claims (e.g. role, uuid)
     */
    public function issueAccessToken(int|string $subjectId, array $claims = []): string
    {
        $now = time();

        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => (string) $subjectId,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($this->accessTtlMinutes * 60),
            'jti' => str_uuid4(),
        ]);

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * @return array<string, mixed> decoded claims
     */
    public function verify(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));

            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new AuthenticationException('The access token has expired.', 'TOKEN_EXPIRED');
        } catch (SignatureInvalidException $e) {
            throw new AuthenticationException('The access token signature is invalid.', 'TOKEN_INVALID');
        } catch (UnexpectedValueException $e) {
            throw new AuthenticationException('The access token is malformed.', 'TOKEN_MALFORMED');
        }
    }

    public function accessTtlSeconds(): int
    {
        return $this->accessTtlMinutes * 60;
    }
}
