<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\AlterBlueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bots', function (AlterBlueprint $table) {
            $table->decimal('top_p', 3, 2, false, 0.95);
            $table->integer('top_k', false, 40);
            $table->json('safety_settings', true);
            $table->string('language', 10, false, 'en');
            $table->string('personality', 255, true);
            $table->string('tone', 50, true);
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (AlterBlueprint $table) {
            $table->dropColumn('top_p');
            $table->dropColumn('top_k');
            $table->dropColumn('safety_settings');
            $table->dropColumn('language');
            $table->dropColumn('personality');
            $table->dropColumn('tone');
        });
    }
};
