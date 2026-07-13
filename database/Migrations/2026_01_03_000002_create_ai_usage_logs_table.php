<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('bot_id');
            $table->unsignedBigInteger('conversation_id', true);
            $table->unsignedBigInteger('message_id', true);
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->string('operation', 30); // chat | chat_stream | embedding | title | suggestions
            $table->integer('prompt_tokens', true);
            $table->integer('completion_tokens', true);
            $table->integer('total_tokens', true);
            $table->integer('request_duration_ms', true);
            $table->integer('response_duration_ms', true);
            $table->decimal('estimated_cost', 10, 6, false, 0);
            $table->enum('status', ['success', 'failed', 'blocked'], false, 'success');
            $table->text('error_message', true);
            $table->dateTime('created_at', false, 'CURRENT_TIMESTAMP');
            $table->unique('uuid');
            $table->index('bot_id');
            $table->index('conversation_id');
            $table->index('provider');
            $table->index('created_at');
            $table->foreign('bot_id', 'bots', 'id');
            $table->foreign('conversation_id', 'conversations', 'id', 'SET NULL');
            $table->foreign('message_id', 'messages', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
