<?php

declare(strict_types=1);

namespace App\Models;

final class Widget
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $botId,
        public readonly string $theme,
        public readonly string $position,
        public readonly ?string $primaryColor,
        public readonly ?string $greetingMessage,
        public readonly string $placeholderText,
        public readonly bool $showBranding,
        public readonly ?string $customCss,
        public readonly array $allowedDomains,
        public readonly bool $isActive,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            uuid: (string) $row['uuid'],
            botId: (int) $row['bot_id'],
            theme: (string) $row['theme'],
            position: (string) $row['position'],
            primaryColor: $row['primary_color'] ?? null,
            greetingMessage: $row['greeting_message'] ?? null,
            placeholderText: (string) $row['placeholder_text'],
            showBranding: (bool) $row['show_branding'],
            customCss: $row['custom_css'] ?? null,
            allowedDomains: !empty($row['allowed_domains']) ? (json_decode((string) $row['allowed_domains'], true) ?: []) : [],
            isActive: (bool) $row['is_active'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'theme' => $this->theme,
            'position' => $this->position,
            'primary_color' => $this->primaryColor,
            'greeting_message' => $this->greetingMessage,
            'placeholder_text' => $this->placeholderText,
            'show_branding' => $this->showBranding,
            'custom_css' => $this->customCss,
            'allowed_domains' => $this->allowedDomains,
            'is_active' => $this->isActive,
        ];
    }

    public function isDomainAllowed(string $origin): bool
    {
        if (empty($this->allowedDomains)) {
            return true;
        }

        $host = parse_url($origin, PHP_URL_HOST) ?: $origin;

        foreach ($this->allowedDomains as $allowed) {
            if ($allowed === '*' || strcasecmp($allowed, $host) === 0) {
                return true;
            }
        }

        return false;
    }
}
