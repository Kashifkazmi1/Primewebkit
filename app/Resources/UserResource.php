<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\User;

final class UserResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(User $user): array
    {
        return $user->toPublicArray();
    }

    /**
     * @param array{user: User, access_token: string, refresh_token: string, expires_in: int} $authResult
     * @return array<string, mixed>
     */
    public static function withTokens(array $authResult): array
    {
        return [
            'user' => self::make($authResult['user']),
            'access_token' => $authResult['access_token'],
            'refresh_token' => $authResult['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $authResult['expires_in'],
        ];
    }
}
