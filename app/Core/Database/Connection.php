<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Manages lazily-created PDO connections keyed by connection name
 * (as defined in config/database.php). Uses persistent-safe defaults
 * suitable for shared hosting (no persistent connections by default).
 */
final class Connection
{
    /**
     * @var array<string, PDO>
     */
    private static array $connections = [];

    public static function get(?string $name = null): PDO
    {
        $name ??= (string) config('database.default');

        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        $config = config("database.connections.{$name}");

        if ($config === null) {
            throw new RuntimeException("Database connection [{$name}] is not configured.");
        }

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            // Never leak credentials or raw driver errors to clients.
            throw new RuntimeException('Database connection failed. Please verify database configuration.', 0, $e);
        }

        return self::$connections[$name] = $pdo;
    }

    /**
     * Primarily for tests: forces reconnection on next get() call.
     */
    public static function disconnect(?string $name = null): void
    {
        if ($name === null) {
            self::$connections = [];

            return;
        }

        unset(self::$connections[$name]);
    }

    public static function beginTransaction(?string $name = null): bool
    {
        return self::get($name)->beginTransaction();
    }

    public static function commit(?string $name = null): bool
    {
        return self::get($name)->commit();
    }

    public static function rollBack(?string $name = null): bool
    {
        return self::get($name)->rollBack();
    }

    public static function inTransaction(?string $name = null): bool
    {
        return self::get($name)->inTransaction();
    }

    /**
     * Run a callback inside a transaction, automatically committing on
     * success and rolling back on any exception, then re-throwing.
     *
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     */
    public static function transaction(callable $callback, ?string $name = null): mixed
    {
        $pdo = self::get($name);
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
}
