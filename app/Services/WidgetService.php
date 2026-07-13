<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\Widget;
use App\Repositories\WidgetRepository;

final class WidgetService
{
    public function __construct(private readonly WidgetRepository $widgets)
    {
    }

    public function getForBot(int $botId): Widget
    {
        $row = $this->widgets->findByBotId($botId);

        if ($row === null) {
            throw new NotFoundException('Widget configuration not found for this bot.');
        }

        return Widget::fromArray($row);
    }

    public function getPublicByBotUuid(string $botUuid): Widget
    {
        $row = $this->widgets->findByBotUuid($botUuid);

        if ($row === null || !$row['is_active']) {
            throw new NotFoundException('Widget not found or is inactive.');
        }

        return Widget::fromArray($row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $botId, array $data): Widget
    {
        $widget = $this->getForBot($botId);

        $allowed = array_intersect_key($data, array_flip([
            'theme', 'position', 'primary_color', 'greeting_message',
            'placeholder_text', 'show_branding', 'custom_css', 'allowed_domains', 'is_active',
        ]));

        if (isset($allowed['allowed_domains']) && is_array($allowed['allowed_domains'])) {
            $allowed['allowed_domains'] = json_encode($allowed['allowed_domains']);
        }

        if (!empty($allowed)) {
            $this->widgets->update($widget->id, $allowed);
        }

        return $this->getForBot($botId);
    }
}
