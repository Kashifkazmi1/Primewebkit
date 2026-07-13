<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Plan;

final class PlanResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Plan $plan): array
    {
        return $plan->toPublicArray();
    }

    /**
     * @param list<Plan> $plans
     * @return list<array<string, mixed>>
     */
    public static function collection(array $plans): array
    {
        return array_map(self::make(...), $plans);
    }
}
