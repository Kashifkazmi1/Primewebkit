<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthenticationException;

/**
 * Verifies Google Identity Services ID tokens (the `credential` the
 * Google sign-in button hands to the browser).
 *
 * Verification is delegated to Google's tokeninfo endpoint, which
 * checks the token's signature and expiry for us; we then confirm the
 * token was issued for OUR client id (aud) by Google (iss) for a
 * verified email. No client secret is involved anywhere in this flow.
 */
final class GoogleAuthService
{
    private const TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';
    private const VALID_ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * @return array{email: string, name: string, picture: ?string, sub: string}
     */
    public function verify(string $idToken): array
    {
        $clientId = (string) config('google.client_id');

        if ($clientId === '') {
            throw new AuthenticationException(
                'Google sign-in is not configured on this server.',
                'GOOGLE_NOT_CONFIGURED'
            );
        }

        $payload = $this->fetchTokenInfo($idToken);

        if (!is_array($payload) || isset($payload['error']) || !isset($payload['aud'])) {
            throw new AuthenticationException('Google sign-in failed. Please try again.', 'GOOGLE_TOKEN_INVALID');
        }

        if (!hash_equals($clientId, (string) $payload['aud'])) {
            throw new AuthenticationException('Google sign-in failed. Please try again.', 'GOOGLE_AUDIENCE_MISMATCH');
        }

        if (!in_array((string) ($payload['iss'] ?? ''), self::VALID_ISSUERS, true)) {
            throw new AuthenticationException('Google sign-in failed. Please try again.', 'GOOGLE_ISSUER_INVALID');
        }

        // tokeninfo validates expiry, but belt-and-braces:
        if (isset($payload['exp']) && (int) $payload['exp'] < time()) {
            throw new AuthenticationException('Google sign-in session expired. Please try again.', 'GOOGLE_TOKEN_EXPIRED');
        }

        $email = mb_strtolower((string) ($payload['email'] ?? ''));

        if ($email === '' || filter_var($payload['email_verified'] ?? 'false', FILTER_VALIDATE_BOOLEAN) !== true) {
            throw new AuthenticationException(
                'Your Google account email is not verified, so it cannot be used to sign in.',
                'GOOGLE_EMAIL_UNVERIFIED'
            );
        }

        return [
            'email' => $email,
            'name' => trim((string) ($payload['name'] ?? '')) !== '' ? (string) $payload['name'] : $email,
            'picture' => isset($payload['picture']) ? (string) $payload['picture'] : null,
            'sub' => (string) ($payload['sub'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchTokenInfo(string $idToken): ?array
    {
        $url = self::TOKENINFO_URL . '?id_token=' . urlencode($idToken);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0 || !is_string($body)) {
            throw new AuthenticationException(
                'Could not reach Google to verify the sign-in. Please try again.',
                'GOOGLE_UNREACHABLE'
            );
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
