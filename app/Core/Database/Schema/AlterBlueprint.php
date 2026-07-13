<?php

declare(strict_types=1);

namespace App\Core\Database\Schema;

/**
 * Additive-only ALTER TABLE builder (add column / add index / add
 * foreign key / drop column). Used via Schema::table().
 */
final class AlterBlueprint
{
    use ColumnDefinitionTrait;

    /**
     * @var list<string>
     */
    private array $statements = [];

    public function __construct(private readonly string $table)
    {
    }

    public function string(string $name, int $length = 255, bool $nullable = false, ?string $default = null): self
    {
        $this->statements[] = "ADD COLUMN " . $this->definition("VARCHAR({$length})", $name, $nullable, $default);

        return $this;
    }

    public function text(string $name, bool $nullable = false): self
    {
        $this->statements[] = 'ADD COLUMN ' . $this->definition('TEXT', $name, $nullable);

        return $this;
    }

    public function integer(string $name, bool $nullable = false, ?int $default = null, bool $unsigned = false): self
    {
        $type = 'INT' . ($unsigned ? ' UNSIGNED' : '');
        $this->statements[] = 'ADD COLUMN ' . $this->definition($type, $name, $nullable, $default);

        return $this;
    }

    public function bigInteger(string $name, bool $nullable = false, ?int $default = null, bool $unsigned = false): self
    {
        $type = 'BIGINT' . ($unsigned ? ' UNSIGNED' : '');
        $this->statements[] = 'ADD COLUMN ' . $this->definition($type, $name, $nullable, $default);

        return $this;
    }

    public function unsignedBigInteger(string $name, bool $nullable = false): self
    {
        return $this->bigInteger($name, $nullable, null, true);
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2, bool $nullable = false, string|float|null $default = null): self
    {
        $this->statements[] = 'ADD COLUMN ' . $this->definition("DECIMAL({$precision},{$scale})", $name, $nullable, $default);

        return $this;
    }

    public function boolean(string $name, bool $nullable = false, ?bool $default = null): self
    {
        $defaultValue = $default === null ? null : ($default ? 1 : 0);
        $this->statements[] = 'ADD COLUMN ' . $this->definition('TINYINT(1)', $name, $nullable, $defaultValue);

        return $this;
    }

    public function json(string $name, bool $nullable = false): self
    {
        $this->statements[] = 'ADD COLUMN ' . $this->definition('JSON', $name, $nullable);

        return $this;
    }

    public function dateTime(string $name, bool $nullable = false, ?string $default = null): self
    {
        $this->statements[] = 'ADD COLUMN ' . $this->definition('DATETIME', $name, $nullable, $default);

        return $this;
    }

    public function dropColumn(string $name): self
    {
        $this->statements[] = "DROP COLUMN `{$name}`";

        return $this;
    }

    public function unique(string|array $columns, ?string $indexName = null): self
    {
        $columns = (array) $columns;
        $name = $indexName ?? $this->table . '_' . implode('_', $columns) . '_unique';
        $cols = implode('`, `', $columns);
        $this->statements[] = "ADD UNIQUE KEY `{$name}` (`{$cols}`)";

        return $this;
    }

    public function index(string|array $columns, ?string $indexName = null): self
    {
        $columns = (array) $columns;
        $name = $indexName ?? $this->table . '_' . implode('_', $columns) . '_index';
        $cols = implode('`, `', $columns);
        $this->statements[] = "ADD KEY `{$name}` (`{$cols}`)";

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

        $this->statements[] = "ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) "
            . "REFERENCES `{$referencesTable}` (`{$referencesColumn}`) "
            . "ON DELETE {$onDelete} ON UPDATE {$onUpdate}";

        return $this;
    }

    /**
     * @return list<string>
     */
    public function toAlterStatements(): array
    {
        if (empty($this->statements)) {
            return [];
        }

        return ["ALTER TABLE `{$this->table}` " . implode(', ', $this->statements) . ';'];
    }
}
