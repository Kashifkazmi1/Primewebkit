<?php

declare(strict_types=1);

use App\Core\Support\Config;

if (!function_exists('base_path')) {
    /**
     * Get the absolute path to the project root, or a path relative to it.
     */
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\')));
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return base_path('config' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\')));
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\')));
    }
}

if (!function_exists('database_path')) {
    function database_path(string $path = ''): string
    {
        return base_path('database' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\')));
    }
}

if (!function_exists('env')) {
    /**
     * Retrieve an environment variable with an optional default,
     * normalising common string literals (true/false/null/empty).
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('config')) {
    /**
     * Retrieve a configuration value using dot notation,
     * e.g. config('database.connections.mysql.host').
     */
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('now_utc')) {
    function now_utc(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}

if (!function_exists('str_uuid4')) {
    /**
     * Generate a RFC 4122 version 4 UUID without requiring the
     * ramsey/uuid package (used in contexts before vendor autoload
     * of optional packages, e.g. very early bootstrap).
     */
    function str_uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('array_dot_get')) {
    /**
     * Retrieve a nested array value using dot notation.
     */
    function array_dot_get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        $segments = explode('.', $key);
        $value = $array;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
