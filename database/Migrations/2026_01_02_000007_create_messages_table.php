<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('conversation_id');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->longText('content');
            $table->integer('tokens_used', true);
            $table->integer('latency_ms', true);
            $table->json('metadata', true);
            $table->dateTime('created_at', false, 'CURRENT_TIMESTAMP');
            $table->unique('uuid');
            $table->index('conversation_id');
            $table->foreign('conversation_id', 'conversations', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
