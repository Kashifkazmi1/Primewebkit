<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SettingsRepository;

/**
 * Admin-editable operational settings, layered on top of (never
 * replacing) the .env-driven config files. Secrets (API keys, SMTP
 * passwords) stay in .env — this only covers non-secret toggles an
 * admin should be able to change without shell access: platform
 * branding, default limits, maintenance mode, etc.
 */
final class SettingsService
{
    /**
     * @var array<string, array{default: mixed, type: string, group: string}>
     */
    private const DEFAULTS = [
        'platform.name' => ['default' => 'AI Chatbot SaaS', 'type' => 'string', 'group' => 'general'],
        'platform.support_email' => ['default' => '', 'type' => 'string', 'group' => 'general'],
        'platform.maintenance_mode' => ['default' => false, 'type' => 'boolean', 'group' => 'general'],
        'branding.logo_url' => ['default' => '', 'type' => 'string', 'group' => 'branding'],
        'branding.primary_color' => ['default' => '#4f46e5', 'type' => 'string', 'group' => 'branding'],
        'uploads.max_file_size_mb' => ['default' => 20, 'type' => 'integer', 'group' => 'uploads'],
        'limits.default_bots' => ['default' => 1, 'type' => 'integer', 'group' => 'limits'],
        'security.login_max_attempts' => ['default' => 5, 'type' => 'integer', 'group' => 'security'],
        'security.login_lockout_minutes' => ['default' => 15, 'type' => 'integer', 'group' => 'security'],
    ];

    public function __construct(private readonly SettingsRepository $settings)
    {
    }

    public function get(string $key): mixed
    {
        $row = $this->settings->findByKey($key);

        if ($row === null) {
            return self::DEFAULTS[$key]['default'] ?? null;
        }

        return $this->decode($row['value'], $row['type']);
    }

    public function set(string $key, mixed $value): void
    {
        $meta = self::DEFAULTS[$key] ?? ['type' => 'string', 'group' => 'general'];
        $this->settings->upsert($key, $value, $meta['type'], $meta['group']);
    }

    /**
     * @return array<string, mixed>
     */
    public function allByGroup(string $group): array
    {
        $result = [];

        foreach (self::DEFAULTS as $key => $meta) {
            if ($meta['group'] === $group) {
                $result[$key] = $this->get($key);
            }
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allGrouped(): array
    {
        $groups = [];

        foreach (self::DEFAULTS as $key => $meta) {
            $groups[$meta['group']][$key] = $this->get($key);
        }

        return $groups;
    }

    private function decode(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => $value === '1',
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
