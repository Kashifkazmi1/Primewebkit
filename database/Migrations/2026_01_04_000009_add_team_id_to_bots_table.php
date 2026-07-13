<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\AlterBlueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bots', function (AlterBlueprint $table) {
            $table->unsignedBigInteger('team_id', true);
            $table->index('team_id');
            $table->foreign('team_id', 'teams', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (AlterBlueprint $table) {
            $table->dropColumn('team_id');
        });
    }
};
