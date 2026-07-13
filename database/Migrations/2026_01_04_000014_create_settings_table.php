<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 150);
            $table->longText('value', true);
            $table->string('type', 20, false, 'string'); // string | boolean | integer | json
            $table->string('group', 50, false, 'general'); // general | mail | gemini | branding | uploads | limits | security
            $table->timestamps();
            $table->unique('key');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
