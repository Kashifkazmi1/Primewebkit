<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;

/**
 * A minimal fluent SQL query builder.
 *
 * All values are bound via prepared statement placeholders — string
 * interpolation of user-supplied data into SQL is never used, which
 * is the platform's primary SQL-injection defence.
 */
final class QueryBuilder
{
    private string $table;
    private array $columns = ['*'];
    private array $wheres = [];
    private array $bindings = [];
    private array $joins = [];
    private array $orders = [];
    private array $groups = [];
    private array $havings = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private bool $withTrashed = false;
    private bool $onlyTrashed = false;
    private bool $softDeletes = true;

    public function __construct(string $table, private readonly ?string $connection = null)
    {
        $this->table = $table;
    }

    public static function table(string $table, ?string $connection = null): self
    {
        return new self($table, $connection);
    }

    public function select(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function withoutSoftDeletes(): self
    {
        $this->softDeletes = false;

        return $this;
    }

    public function withTrashed(): self
    {
        $this->withTrashed = true;

        return $this;
    }

    public function onlyTrashed(): self
    {
        $this->onlyTrashed = true;

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $placeholder = $this->placeholder($column);
        $this->wheres[] = "AND {$this->quoteIdentifier($column)} {$operator} {$placeholder}";
        $this->bindings[$placeholder] = $value;

        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $placeholder = $this->placeholder($column);
        $this->wheres[] = "OR {$this->quoteIdentifier($column)} {$operator} {$placeholder}";
        $this->bindings[$placeholder] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            $this->wheres[] = 'AND 1 = 0';

            return $this;
        }

        $placeholders = [];

        foreach (array_values($values) as $i => $value) {
            $placeholder = $this->placeholder($column . '_in_' . $i);
            $placeholders[] = $placeholder;
            $this->bindings[$placeholder] = $value;
        }

        $this->wheres[] = "AND {$this->quoteIdentifier($column)} IN (" . implode(', ', $placeholders) . ')';

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = "AND {$this->quoteIdentifier($column)} IS NULL";

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = "AND {$this->quoteIdentifier($column)} IS NOT NULL";

        return $this;
    }

    public function whereRaw(string $rawSql, array $bindings = []): self
    {
        $this->wheres[] = "AND ({$rawSql})";

        foreach ($bindings as $key => $value) {
            $this->bindings[$key] = $value;
        }

        return $this;
    }

    public function whereBetween(string $column, mixed $start, mixed $end): self
    {
        $startPlaceholder = $this->placeholder($column . '_start');
        $endPlaceholder = $this->placeholder($column . '_end');

        $this->wheres[] = "AND {$this->quoteIdentifier($column)} BETWEEN {$startPlaceholder} AND {$endPlaceholder}";
        $this->bindings[$startPlaceholder] = $start;
        $this->bindings[$endPlaceholder] = $end;

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = "{$this->quoteIdentifier($column)} {$direction}";

        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        $this->groups = [...$this->groups, ...$columns];

        return $this;
    }

    public function having(string $rawSql, array $bindings = []): self
    {
        $this->havings[] = $rawSql;

        foreach ($bindings as $key => $value) {
            $this->bindings[$key] = $value;
        }

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function forPage(int $page, int $perPage): self
    {
        $page = max(1, $page);

        return $this->limit($perPage)->offset(($page - 1) * $perPage);
    }

    // ---------------------------------------------------------------
    // Execution
    // ---------------------------------------------------------------

    public function get(): array
    {
        [$sql, $bindings] = $this->buildSelect();

        $statement = Connection::get($this->connection)->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function first(): ?array
    {
        $results = $this->limit(1)->get();

        return $results[0] ?? null;
    }

    public function find(int|string $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, '=', $id)->first();
    }

    public function count(): int
    {
        $originalColumns = $this->columns;
        $this->columns = ['COUNT(*) AS aggregate'];

        [$sql, $bindings] = $this->buildSelect(ignoreLimitOffset: true);

        $statement = Connection::get($this->connection)->prepare($sql);
        $statement->execute($bindings);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $this->columns = $originalColumns;

        return (int) ($row['aggregate'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $total = $this->count();
        $data = $this->forPage($page, $perPage)->get();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    public function insertGetId(array $data): string|int
    {
        $columns = array_keys($data);
        $quotedColumns = array_map(fn ($col) => "`{$col}`", $columns);
        $placeholders = array_map(fn ($col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $quotedColumns),
            implode(', ', $placeholders)
        );

        $pdo = Connection::get($this->connection);
        $statement = $pdo->prepare($sql);
        $statement->execute($this->normalizeBindings($data, $columns));

        return $pdo->lastInsertId();
    }

    public function insert(array $data): bool
    {
        $columns = array_keys($data);
        $quotedColumns = array_map(fn ($col) => "`{$col}`", $columns);
        $placeholders = array_map(fn ($col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $quotedColumns),
            implode(', ', $placeholders)
        );

        $statement = Connection::get($this->connection)->prepare($sql);

        return $statement->execute($this->normalizeBindings($data, $columns));
    }

    public function update(array $data): int
    {
        $setParts = [];
        $updateBindings = [];

        foreach ($data as $column => $value) {
            $placeholder = 'set_' . $column;
            $setParts[] = "`{$column}` = :{$placeholder}";
            $updateBindings[":{$placeholder}"] = $value;
        }

        [$whereSql, $whereBindings] = $this->buildWhere();

        $sql = sprintf('UPDATE %s SET %s %s', $this->table, implode(', ', $setParts), $whereSql);

        $statement = Connection::get($this->connection)->prepare($sql);
        $statement->execute([...$updateBindings, ...$whereBindings]);

        return $statement->rowCount();
    }

    public function delete(): int
    {
        [$whereSql, $whereBindings] = $this->buildWhere();

        $sql = sprintf('DELETE FROM %s %s', $this->table, $whereSql);

        $statement = Connection::get($this->connection)->prepare($sql);
        $statement->execute($whereBindings);

        return $statement->rowCount();
    }

    /**
     * Soft delete: sets deleted_at = NOW() instead of removing the row.
     */
    public function softDelete(): int
    {
        return $this->update(['deleted_at' => now_utc()->format('Y-m-d H:i:s')]);
    }

    public function restore(): int
    {
        return $this->update(['deleted_at' => null]);
    }

    // ---------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------

    private function placeholder(string $column): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '_', $column);

        return ':' . $safe . '_' . count($this->bindings);
    }

    /**
     * Wraps a column (optionally "table.column") in backticks so
     * reserved words (e.g. `group`, `order`) never break generated SQL.
     */
    private function quoteIdentifier(string $column): string
    {
        if ($column === '*') {
            return $column;
        }

        return implode('.', array_map(fn (string $part) => "`{$part}`", explode('.', $column)));
    }

    private function buildSelect(bool $ignoreLimitOffset = false): array
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $this->columns), $this->table);

        foreach ($this->joins as $join) {
            $sql .= ' ' . $join;
        }

        [$whereSql, $bindings] = $this->buildWhere();
        $sql .= ' ' . $whereSql;

        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->havings);
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if (!$ignoreLimitOffset) {
            if ($this->limit !== null) {
                $sql .= ' LIMIT ' . $this->limit;
            }

            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        return [$sql, $bindings];
    }

    private function buildWhere(): array
    {
        $wheres = $this->wheres;

        if ($this->softDeletes && !$this->withTrashed) {
            $column = "`{$this->table}`.`deleted_at`";
            array_unshift($wheres, $this->onlyTrashed ? "AND {$column} IS NOT NULL" : "AND {$column} IS NULL");
        }

        if (empty($wheres)) {
            return ['', []];
        }

        $clause = implode(' ', $wheres);
        // Strip the leading boolean connector (AND/OR) from the first condition.
        $clause = preg_replace('/^(AND|OR)\s+/', '', $clause, 1);

        return ['WHERE ' . $clause, $this->bindings];
    }

    private function normalizeBindings(array $data, array $columns): array
    {
        $bindings = [];

        foreach ($columns as $column) {
            $bindings[':' . $column] = $data[$column];
        }

        return $bindings;
    }
}
