<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('bot_id');
            $table->string('fingerprint', 64);
            $table->string('ip_address', 45, true);
            $table->string('user_agent', 255, true);
            $table->string('country_code', 2, true);
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at');
            $table->json('metadata', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->unique(['bot_id', 'fingerprint']);
            $table->index('bot_id');
            $table->foreign('bot_id', 'bots', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
