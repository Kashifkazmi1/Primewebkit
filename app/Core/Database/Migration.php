<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Every migration file in /database/Migrations must extend this class
 * and implement up() (apply) and down() (rollback).
 */
abstract class Migration
{
    abstract public function up(): void;

    abstract public function down(): void;
}
