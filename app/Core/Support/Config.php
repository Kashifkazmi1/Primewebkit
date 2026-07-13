<?php

declare(strict_types=1);

namespace App\Core\Support;

/**
 * Lightweight configuration repository.
 *
 * Lazily loads /config/{file}.php arrays on first access and caches
 * them in memory for the remainder of the request lifecycle.
 */
final class Config
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $items = [];

    /**
     * @var array<string, bool>
     */
    private static array $loaded = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key, 2);
        $file = $segments[0];

        self::loadFile($file);

        if (!isset(self::$items[$file])) {
            return $default;
        }

        if (!isset($segments[1])) {
            return self::$items[$file];
        }

        return array_dot_get(self::$items[$file], $segments[1], $default);
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        self::loadFile($file);

        if (empty($segments)) {
            self::$items[$file] = $value;

            return;
        }

        $ref = &self::$items[$file];

        foreach ($segments as $i => $segment) {
            if ($i === array_key_last($segments)) {
                $ref[$segment] = $value;

                break;
            }

            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }

            $ref = &$ref[$segment];
        }
    }

    public static function all(): array
    {
        $configPath = config_path();

        if (is_dir($configPath)) {
            foreach (glob($configPath . '/*.php') ?: [] as $path) {
                self::loadFile(basename($path, '.php'));
            }
        }

        return self::$items;
    }

    /**
     * Reset the cache. Primarily useful for the test suite.
     */
    public static function flush(): void
    {
        self::$items = [];
        self::$loaded = [];
    }

    private static function loadFile(string $file): void
    {
        if (isset(self::$loaded[$file])) {
            return;
        }

        $path = config_path($file . '.php');

        self::$items[$file] = is_file($path) ? (require $path) : [];
        self::$loaded[$file] = true;
    }
}
