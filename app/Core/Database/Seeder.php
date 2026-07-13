<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Every seeder in /database/Seeds must extend this class and
 * implement run().
 */
abstract class Seeder
{
    abstract public function run(): void;
}
