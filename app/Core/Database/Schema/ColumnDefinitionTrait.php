<?php

declare(strict_types=1);

namespace App\Core\Database\Schema;

/**
 * Shared SQL column-definition rendering used by both the
 * create-table Blueprint and the additive AlterBlueprint.
 */
trait ColumnDefinitionTrait
{
    private function definition(string $type, string $name, bool $nullable, mixed $default = null): string
    {
        $sql = "`{$name}` {$type}";
        $sql .= $nullable ? ' NULL' : ' NOT NULL';

        if ($default !== null) {
            $sql .= is_string($default) && !str_starts_with(strtoupper($default), 'CURRENT_TIMESTAMP')
                ? " DEFAULT '" . str_replace("'", "''", $default) . "'"
                : " DEFAULT {$default}";
        } elseif ($nullable) {
            $sql .= ' DEFAULT NULL';
        }

        return $sql;
    }
}
