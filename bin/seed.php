#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Core\Database\SeedRunner;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->load();
}

date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

fwrite(STDOUT, "AI Chatbot SaaS — Seed Runner\n");
fwrite(STDOUT, str_repeat('-', 40) . "\n");

try {
    $runner = new SeedRunner();
    $executed = $runner->run();

    if (empty($executed)) {
        fwrite(STDOUT, "No seeders found in database/Seeds.\n");
    } else {
        foreach ($executed as $file) {
            fwrite(STDOUT, "Seeded: {$file}\n");
        }
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "Seeding failed: {$e->getMessage()}\n");
    exit(1);
}

fwrite(STDOUT, str_repeat('-', 40) . "\n");
fwrite(STDOUT, "Done.\n");
