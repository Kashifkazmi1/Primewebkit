<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Repositories\ApiKeyRepository;

/**
 * Manages personal API keys used for programmatic access to the
 * platform's REST API (as opposed to the user-facing JWT session
 * tokens). The raw key is shown to the user exactly once, at creation
 * time — only its SHA-256 hash and a short display prefix are stored.
 */
final class ApiKeyService
{
    private const PREFIX = 'sk_live_';

    public function __construct(
        private readonly ApiKeyRepository $apiKeys,
        private readonly TokenService $tokens,
    ) {
    }

    /**
     * @param list<string> $scopes
     * @return array{id: string, name: string, key: string, key_prefix: string, scopes: list<string>, created_at: string}
     */
    public function create(int $userId, string $name, ?string $expiresAt = null, array $scopes = []): array
    {
        $rawSecret = $this->tokens->generate(24);
        $fullKey = self::PREFIX . $rawSecret;
        $displayPrefix = self::PREFIX . mb_substr($rawSecret, 0, 6);

        $id = (int) $this->apiKeys->create([
            'uuid' => str_uuid4(),
            'user_id' => $userId,
            'name' => $name,
            'key_prefix' => $displayPrefix,
            'key_hash' => $this->tokens->hash($fullKey),
            'expires_at' => $expiresAt,
            'scopes' => empty($scopes) ? null : json_encode($scopes),
        ]);

        $row = $this->apiKeys->find($id);

        return [
            'id' => $row['uuid'],
            'name' => $row['name'],
            'key' => $fullKey, // shown once — never retrievable again
            'key_prefix' => $row['key_prefix'],
            'scopes' => $scopes,
            'created_at' => $row['created_at'],
        ];
    }

    /**
     * Revokes the existing key and issues a brand new one with the
     * same name/scopes/expiry — used when a key may have leaked, so
     * the old value stops working immediately while service
     * continuity is preserved via the new value.
     *
     * @return array{id: string, name: string, key: string, key_prefix: string, scopes: list<string>, created_at: string}
     */
    public function rotate(string $uuid, int $userId): array
    {
        $row = $this->apiKeys->findByUuidForUser($uuid, $userId);

        if ($row === null) {
            throw new NotFoundException('API key not found.');
        }

        $this->apiKeys->revoke((int) $row['id']);

        $scopes = !empty($row['scopes']) ? (json_decode((string) $row['scopes'], true) ?: []) : [];
        $result = $this->create($userId, (string) $row['name'], $row['expires_at'], $scopes);

        $this->apiKeys->update(
            (int) \App\Core\Database\QueryBuilder::table('api_keys')->withoutSoftDeletes()->where('uuid', '=', $result['id'])->first()['id'],
            ['rotated_from' => $row['id']]
        );

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return array_map(
            fn (array $row) => [
                'id' => $row['uuid'],
                'name' => $row['name'],
                'key_prefix' => $row['key_prefix'],
                'scopes' => !empty($row['scopes']) ? (json_decode((string) $row['scopes'], true) ?: []) : [],
                'last_used_at' => $row['last_used_at'],
                'expires_at' => $row['expires_at'],
                'revoked' => $row['revoked_at'] !== null,
                'created_at' => $row['created_at'],
            ],
            $this->apiKeys->forUser($userId)
        );
    }

    public function revoke(string $uuid, int $userId): void
    {
        $row = $this->apiKeys->findByUuidForUser($uuid, $userId);

        if ($row === null) {
            throw new NotFoundException('API key not found.');
        }

        $this->apiKeys->revoke((int) $row['id']);
    }

    /**
     * Verifies a raw API key presented via `X-API-Key` and returns the
     * owning user's id, or null if invalid/expired/revoked.
     */
    public function resolveUserIdFromKey(string $rawKey): ?int
    {
        $row = $this->apiKeys->findByHash($this->tokens->hash($rawKey));

        if ($row === null) {
            return null;
        }

        if ($row['expires_at'] !== null && strtotime($row['expires_at']) < time()) {
            return null;
        }

        $this->apiKeys->touchLastUsed((int) $row['id']);

        return (int) $row['user_id'];
    }
}
