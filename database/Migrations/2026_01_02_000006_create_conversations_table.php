<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('bot_id');
            $table->unsignedBigInteger('visitor_id', true);
            $table->string('session_id', 64);
            $table->enum('status', ['active', 'closed'], false, 'active');
            $table->string('title', 255, true);
            $table->integer('message_count', false, 0);
            $table->json('metadata', true);
            $table->dateTime('started_at');
            $table->dateTime('last_message_at', true);
            $table->dateTime('ended_at', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->index('bot_id');
            $table->index('visitor_id');
            $table->index('session_id');
            $table->index('status');
            $table->foreign('bot_id', 'bots', 'id');
            $table->foreign('visitor_id', 'visitors', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
