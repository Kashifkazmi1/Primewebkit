<?php

declare(strict_types=1);

use App\Core\Database\Connection;
use App\Core\Database\Migration;

/**
 * The `period` column was sized for 'YYYY-MM' (7 chars) but the code
 * also uses the sentinel value 'lifetime' (8 chars) for point-in-time
 * gauge metrics (knowledge_mb, storage_mb) — every insert using that
 * sentinel failed with a truncation error. Caught by running actual
 * knowledge-ingestion requests against MySQL, not by code review.
 */
return new class extends Migration {
    public function up(): void
    {
        Connection::get()->exec('ALTER TABLE usage_counters MODIFY COLUMN period VARCHAR(20) NOT NULL');
    }

    public function down(): void
    {
        Connection::get()->exec('ALTER TABLE usage_counters MODIFY COLUMN period VARCHAR(7) NOT NULL');
    }
};
