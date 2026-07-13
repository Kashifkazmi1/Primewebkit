<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\AlterBlueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('api_keys', function (AlterBlueprint $table) {
            $table->json('scopes', true);
            $table->unsignedBigInteger('rotated_from', true);
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (AlterBlueprint $table) {
            $table->dropColumn('scopes');
            $table->dropColumn('rotated_from');
        });
    }
};
