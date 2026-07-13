<?php

declare(strict_types=1);

namespace App\Core\Database\Schema;

use App\Core\Database\Connection;
use Closure;

/**
 * Static facade used inside migrations to create/alter/drop tables.
 *
 * Example:
 *   Schema::create('users', function (Blueprint $table) {
 *       $table->id();
 *       $table->string('email')->unique();
 *   });
 */
final class Schema
{
    public static function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        Connection::get()->exec($blueprint->toCreateSql());
    }

    public static function drop(string $table): void
    {
        Connection::get()->exec("DROP TABLE IF EXISTS `{$table}`;");
    }

    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }

    public static function raw(string $sql): void
    {
        Connection::get()->exec($sql);
    }

    public static function hasTable(string $table): bool
    {
        $statement = Connection::get()->prepare(
            'SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $statement->execute(['table' => $table]);

        return (int) ($statement->fetch()['total'] ?? 0) > 0;
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $statement = Connection::get()->prepare(
            'SELECT COUNT(*) AS total FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
        );
        $statement->execute(['table' => $table, 'column' => $column]);

        return (int) ($statement->fetch()['total'] ?? 0) > 0;
    }

    /**
     * Add columns / indexes to an existing table using raw ALTER TABLE
     * statements composed from a Blueprint's column/index definitions.
     * Kept intentionally simple (additive-only) — sufficient for this
     * platform's migration needs without a full diffing ALTER engine.
     */
    public static function table(string $table, Closure $callback): void
    {
        $blueprint = new AlterBlueprint($table);
        $callback($blueprint);

        foreach ($blueprint->toAlterStatements() as $sql) {
            Connection::get()->exec($sql);
        }
    }
}
