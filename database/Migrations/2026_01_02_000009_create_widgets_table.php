<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('bot_id');
            $table->enum('theme', ['light', 'dark'], false, 'light');
            $table->enum('position', ['bottom-right', 'bottom-left'], false, 'bottom-right');
            $table->string('primary_color', 20, true);
            $table->string('greeting_message', 500, true);
            $table->string('placeholder_text', 150, false, 'Type your message...');
            $table->boolean('show_branding', false, true);
            $table->longText('custom_css', true);
            $table->json('allowed_domains', true);
            $table->boolean('is_active', false, true);
            $table->timestamps();
            $table->unique('uuid');
            $table->unique('bot_id');
            $table->foreign('bot_id', 'bots', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
