<?php

declare(strict_types=1);

namespace App\Core\Database;

use RuntimeException;

/**
 * Tracks and executes migration files found in /database/Migrations.
 *
 * Each migration file must `return new class extends Migration { ... };`
 * Files are ordered lexically, so they are named with a leading
 * timestamp, e.g. 2026_01_01_000000_create_users_table.php
 */
final class MigrationRunner
{
    private readonly string $migrationsPath;
    private readonly string $migrationsTable;

    public function __construct(?string $migrationsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? database_path('Migrations');
        $this->migrationsTable = (string) config('database.migrations_table', 'migrations');
        $this->ensureMigrationsTableExists();
    }

    /**
     * Run all pending migrations. Returns the list of migration
     * filenames that were executed.
     *
     * @return list<string>
     */
    public function migrate(): array
    {
        $pending = array_diff($this->allMigrationFiles(), $this->ranMigrations());

        if (empty($pending)) {
            return [];
        }

        sort($pending);
        $batch = $this->nextBatchNumber();
        $executed = [];

        foreach ($pending as $file) {
            $this->runMigration($file, 'up');
            $this->recordMigration($file, $batch);
            $executed[] = $file;
        }

        return $executed;
    }

    /**
     * Roll back the most recent batch of migrations.
     *
     * @return list<string>
     */
    public function rollback(): array
    {
        $lastBatch = $this->lastBatchNumber();

        if ($lastBatch === 0) {
            return [];
        }

        $files = $this->migrationsInBatch($lastBatch);
        rsort($files);

        foreach ($files as $file) {
            $this->runMigration($file, 'down');
            $this->deleteMigrationRecord($file);
        }

        return $files;
    }

    /**
     * Drop every table then re-run every migration from scratch.
     *
     * @return list<string>
     */
    public function fresh(): array
    {
        $this->dropAllTables();
        $this->ensureMigrationsTableExists();

        $files = $this->allMigrationFiles();
        sort($files);

        $batch = 1;

        foreach ($files as $file) {
            $this->runMigration($file, 'up');
            $this->recordMigration($file, $batch);
        }

        return $files;
    }

    /**
     * @return list<array{migration: string, batch: int, ran: bool}>
     */
    public function status(): array
    {
        $ran = $this->ranMigrationsWithBatch();
        $all = $this->allMigrationFiles();
        sort($all);

        return array_map(
            fn (string $file) => [
                'migration' => $file,
                'batch' => $ran[$file] ?? null,
                'ran' => isset($ran[$file]),
            ],
            $all
        );
    }

    private function runMigration(string $file, string $direction): void
    {
        $path = $this->migrationsPath . DIRECTORY_SEPARATOR . $file;

        if (!is_file($path)) {
            throw new RuntimeException("Migration file not found: {$file}");
        }

        /** @var Migration $migration */
        $migration = require $path;

        if (!$migration instanceof Migration) {
            throw new RuntimeException("Migration file [{$file}] must return an instance of " . Migration::class);
        }

        $migration->{$direction}();
    }

    private function allMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php') ?: [];

        return array_map('basename', $files);
    }

    private function ensureMigrationsTableExists(): void
    {
        Connection::get()->exec(sprintf(
            'CREATE TABLE IF NOT EXISTS `%s` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `%s_migration_unique` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
            $this->migrationsTable,
            $this->migrationsTable
        ));
    }

    private function ranMigrations(): array
    {
        $statement = Connection::get()->query("SELECT migration FROM `{$this->migrationsTable}`");

        return $statement !== false ? array_column($statement->fetchAll(), 'migration') : [];
    }

    private function ranMigrationsWithBatch(): array
    {
        $statement = Connection::get()->query("SELECT migration, batch FROM `{$this->migrationsTable}`");
        $rows = $statement !== false ? $statement->fetchAll() : [];

        $result = [];

        foreach ($rows as $row) {
            $result[$row['migration']] = (int) $row['batch'];
        }

        return $result;
    }

    private function migrationsInBatch(int $batch): array
    {
        $statement = Connection::get()->prepare("SELECT migration FROM `{$this->migrationsTable}` WHERE batch = :batch");
        $statement->execute(['batch' => $batch]);

        return array_column($statement->fetchAll(), 'migration');
    }

    private function nextBatchNumber(): int
    {
        return $this->lastBatchNumber() + 1;
    }

    private function lastBatchNumber(): int
    {
        $statement = Connection::get()->query("SELECT MAX(batch) AS max_batch FROM `{$this->migrationsTable}`");
        $row = $statement !== false ? $statement->fetch() : null;

        return (int) ($row['max_batch'] ?? 0);
    }

    private function recordMigration(string $file, int $batch): void
    {
        $statement = Connection::get()->prepare(
            "INSERT INTO `{$this->migrationsTable}` (migration, batch, ran_at) VALUES (:migration, :batch, :ran_at)"
        );
        $statement->execute([
            'migration' => $file,
            'batch' => $batch,
            'ran_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);
    }

    private function deleteMigrationRecord(string $file): void
    {
        $statement = Connection::get()->prepare("DELETE FROM `{$this->migrationsTable}` WHERE migration = :migration");
        $statement->execute(['migration' => $file]);
    }

    private function dropAllTables(): void
    {
        $pdo = Connection::get();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $statement = $pdo->query('SHOW TABLES');
        $tables = $statement !== false ? $statement->fetchAll(\PDO::FETCH_COLUMN) : [];

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
