<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('bot_id');
            $table->unsignedBigInteger('conversation_id', true);
            $table->string('name', 150, true);
            $table->string('email', 190, true);
            $table->string('phone', 30, true);
            $table->json('metadata', true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('uuid');
            $table->index('bot_id');
            $table->index('email');
            $table->foreign('bot_id', 'bots', 'id');
            $table->foreign('conversation_id', 'conversations', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
