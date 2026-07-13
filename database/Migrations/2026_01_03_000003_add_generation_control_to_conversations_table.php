<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\AlterBlueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (AlterBlueprint $table) {
            $table->dateTime('cancel_requested_at', true);
            $table->dateTime('generating_since', true);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (AlterBlueprint $table) {
            $table->dropColumn('cancel_requested_at');
            $table->dropColumn('generating_since');
        });
    }
};
