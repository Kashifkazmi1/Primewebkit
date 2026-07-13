<?php

declare(strict_types=1);

namespace App\Core\Database;

use RuntimeException;

/**
 * Executes every seeder file in /database/Seeds, in lexical filename
 * order. Each file must `return new class extends Seeder { ... };`
 */
final class SeedRunner
{
    public function __construct(private readonly ?string $seedsPath = null)
    {
    }

    /**
     * @return list<string> filenames executed
     */
    public function run(): array
    {
        $path = $this->seedsPath ?? database_path('Seeds');

        if (!is_dir($path)) {
            return [];
        }

        $files = glob($path . '/*.php') ?: [];
        sort($files);

        $executed = [];

        foreach ($files as $file) {
            /** @var Seeder $seeder */
            $seeder = require $file;

            if (!$seeder instanceof Seeder) {
                throw new RuntimeException('Seed file [' . basename($file) . '] must return an instance of ' . Seeder::class);
            }

            $seeder->run();
            $executed[] = basename($file);
        }

        return $executed;
    }
}
