<?php

declare(strict_types=1);

namespace App\Core\Database\Schema;

/**
 * Fluent column/index definition builder used inside migrations.
 * Renders to raw MySQL 8 DDL when the migration is executed.
 */
final class Blueprint
{
    use ColumnDefinitionTrait;

    /**
     * @var list<string>
     */
    private array $columns = [];

    /**
     * @var list<string>
     */
    private array $indexes = [];

    /**
     * @var list<string>
     */
    private array $foreignKeys = [];

    private string $engine = 'InnoDB';
    private string $charset = 'utf8mb4';
    private string $collation = 'utf8mb4_unicode_ci';

    public function __construct(private readonly string $table)
    {
    }

    public function id(string $name = 'id'): self
    {
        $this->columns[] = "`{$name}` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";

        return $this;
    }

    public function uuid(string $name = 'uuid'): self
    {
        $this->columns[] = "`{$name}` CHAR(36) NOT NULL";

        return $this;
    }

    public function string(string $name, int $length = 255, bool $nullable = false, ?string $default = null): self
    {
        $this->columns[] = $this->definition("VARCHAR({$length})", $name, $nullable, $default);

        return $this;
    }

    public function text(string $name, bool $nullable = false): self
    {
        $this->columns[] = $this->definition('TEXT', $name, $nullable);

        return $this;
    }

    public function longText(string $name, bool $nullable = false): self
    {
        $this->columns[] = $this->definition('LONGTEXT', $name, $nullable);

        return $this;
    }

    public function integer(string $name, bool $nullable = false, ?int $default = null, bool $unsigned = false): self
    {
        $type = 'INT' . ($unsigned ? ' UNSIGNED' : '');
        $this->columns[] = $this->definition($type, $name, $nullable, $default);

        return $this;
    }

    public function bigInteger(string $name, bool $nullable = false, ?int $default = null, bool $unsigned = false): self
    {
        $type = 'BIGINT' . ($unsigned ? ' UNSIGNED' : '');
        $this->columns[] = $this->definition($type, $name, $nullable, $default);

        return $this;
    }

    public function unsignedBigInteger(string $name, bool $nullable = false): self
    {
        return $this->bigInteger($name, $nullable, null, true);
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2, bool $nullable = false, string|float|null $default = null): self
    {
        $this->columns[] = $this->definition("DECIMAL({$precision},{$scale})", $name, $nullable, $default);

        return $this;
    }

    public function boolean(string $name, bool $nullable = false, ?bool $default = null): self
    {
        $defaultValue = $default === null ? null : ($default ? 1 : 0);
        $this->columns[] = $this->definition('TINYINT(1)', $name, $nullable, $defaultValue);

        return $this;
    }

    public function date(string $name, bool $nullable = false): self
    {
        $this->columns[] = $this->definition('DATE', $name, $nullable);

        return $this;
    }

    public function dateTime(string $name, bool $nullable = false, ?string $default = null): self
    {
        $this->columns[] = $this->definition('DATETIME', $name, $nullable, $default);

        return $this;
    }

    public function timestamp(string $name, bool $nullable = true): self
    {
        $this->columns[] = $this->definition('TIMESTAMP', $name, $nullable);

        return $this;
    }

    public function json(string $name, bool $nullable = false): self
    {
        $this->columns[] = $this->definition('JSON', $name, $nullable);

        return $this;
    }

    public function enum(string $name, array $values, bool $nullable = false, ?string $default = null): self
    {
        $escaped = implode(',', array_map(fn (string $v) => "'" . str_replace("'", "''", $v) . "'", $values));
        $this->columns[] = $this->definition("ENUM({$escaped})", $name, $nullable, $default);

        return $this;
    }

    /**
     * Adds created_at and updated_at DATETIME columns.
     */
    public function timestamps(): self
    {
        $this->columns[] = '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $this->columns[] = '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';

        return $this;
    }

    /**
     * Adds a nullable deleted_at DATETIME column for soft deletes.
     */
    public function softDeletes(): self
    {
        $this->columns[] = '`deleted_at` DATETIME NULL DEFAULT NULL';

        return $this;
    }

    public function rememberToken(string $name = 'remember_token'): self
    {
        return $this->string($name, 100, true);
    }

    public function unique(string|array $columns, ?string $indexName = null): self
    {
        $columns = (array) $columns;
        $name = $indexName ?? $this->table . '_' . implode('_', $columns) . '_unique';
        $cols = implode('`, `', $columns);
        $this->indexes[] = "UNIQUE KEY `{$name}` (`{$cols}`)";

        return $this;
    }

    public function index(string|array $columns, ?string $indexName = null): self
    {
        $columns = (array) $columns;
        $name = $indexName ?? $this->table . '_' . implode('_', $columns) . '_index';
        $cols = implode('`, `', $columns);
        $this->indexes[] = "KEY `{$name}` (`{$cols}`)";

        return $this;
    }

    public function foreign(
        string $column,
        string $referencesTable,
        string $referencesColumn = 'id',
        string $onDelete = 'CASCADE',
        string $onUpdate = 'CASCADE'
    ): self {
        $name = "fk_{$this->table}_{$column}";

        $this->foreignKeys[] = "CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) "
            . "REFERENCES `{$referencesTable}` (`{$referencesColumn}`) "
            . "ON DELETE {$onDelete} ON UPDATE {$onUpdate}";

        return $this;
    }

    public function engine(string $engine): self
    {
        $this->engine = $engine;

        return $this;
    }

    public function toCreateSql(): string
    {
        $definitions = [...$this->columns, ...$this->indexes, ...$this->foreignKeys];

        return sprintf(
            "CREATE TABLE IF NOT EXISTS `%s` (\n    %s\n) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s;",
            $this->table,
            implode(",\n    ", $definitions),
            $this->engine,
            $this->charset,
            $this->collation
        );
    }

}
