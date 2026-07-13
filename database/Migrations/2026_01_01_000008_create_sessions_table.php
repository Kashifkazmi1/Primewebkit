<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('user_id');
            $table->string('refresh_token_hash', 255);
            $table->string('user_agent', 255, true);
            $table->string('ip_address', 45, true);
            $table->boolean('is_revoked', false, false);
            $table->dateTime('expires_at');
            $table->dateTime('last_used_at', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->unique('refresh_token_hash');
            $table->index('user_id');
            $table->index('expires_at');
            $table->foreign('user_id', 'users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
