<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Widget;

final class WidgetResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Widget $widget): array
    {
        return $widget->toPublicArray();
    }
}
