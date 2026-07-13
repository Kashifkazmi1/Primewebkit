#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Core\Database\MigrationRunner;

require dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->load();
}

date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

$command = $argv[1] ?? 'migrate';

$runner = new MigrationRunner();

fwrite(STDOUT, "AI Chatbot SaaS — Migration Runner\n");
fwrite(STDOUT, str_repeat('-', 40) . "\n");

try {
    switch ($command) {
        case 'migrate':
            $executed = $runner->migrate();
            if (empty($executed)) {
                fwrite(STDOUT, "Nothing to migrate. Database is up to date.\n");
                break;
            }
            foreach ($executed as $file) {
                fwrite(STDOUT, "Migrated: {$file}\n");
            }
            break;

        case 'rollback':
            $rolledBack = $runner->rollback();
            if (empty($rolledBack)) {
                fwrite(STDOUT, "Nothing to roll back.\n");
                break;
            }
            foreach ($rolledBack as $file) {
                fwrite(STDOUT, "Rolled back: {$file}\n");
            }
            break;

        case 'fresh':
            fwrite(STDOUT, "WARNING: this will DROP ALL TABLES in the configured database.\n");
            fwrite(STDOUT, "Type 'yes' to continue: ");
            $confirmation = trim((string) fgets(STDIN));

            if (strtolower($confirmation) !== 'yes') {
                fwrite(STDOUT, "Aborted.\n");
                exit(0);
            }

            $executed = $runner->fresh();
            foreach ($executed as $file) {
                fwrite(STDOUT, "Migrated: {$file}\n");
            }
            break;

        case 'status':
            foreach ($runner->status() as $row) {
                $state = $row['ran'] ? "Ran (batch {$row['batch']})" : 'Pending';
                fwrite(STDOUT, sprintf("%-60s %s\n", $row['migration'], $state));
            }
            break;

        default:
            fwrite(STDERR, "Unknown command [{$command}]. Use: migrate | rollback | fresh | status\n");
            exit(1);
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}

fwrite(STDOUT, str_repeat('-', 40) . "\n");
fwrite(STDOUT, "Done.\n");
