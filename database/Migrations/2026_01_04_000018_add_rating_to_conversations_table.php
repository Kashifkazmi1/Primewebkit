<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\AlterBlueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (AlterBlueprint $table) {
            $table->integer('rating', true);
            $table->text('rating_comment', true);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (AlterBlueprint $table) {
            $table->dropColumn('rating');
            $table->dropColumn('rating_comment');
        });
    }
};
