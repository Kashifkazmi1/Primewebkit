<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Team;

final class TeamResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Team $team): array
    {
        return $team->toPublicArray();
    }
}
