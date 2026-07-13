<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 150);
            $table->text('description', true);
            $table->string('avatar_path', 255, true);
            $table->enum('status', ['draft', 'training', 'active', 'archived'], false, 'draft');
            $table->string('ai_provider', 50, false, 'gemini');
            $table->string('model', 100, false, 'gemini-1.5-flash');
            $table->longText('system_prompt', true);
            $table->decimal('temperature', 2, 1, false, 0.7);
            $table->integer('max_output_tokens', false, 2048);
            $table->string('welcome_message', 500, true);
            $table->string('primary_color', 20, true);
            $table->boolean('is_public', false, false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('uuid');
            $table->index('user_id');
            $table->index('status');
            $table->foreign('user_id', 'users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
