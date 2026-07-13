<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 150);
            $table->string('key_prefix', 16);
            $table->string('key_hash', 255);
            $table->dateTime('last_used_at', true);
            $table->dateTime('expires_at', true);
            $table->dateTime('revoked_at', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->unique('key_hash');
            $table->index('user_id');
            $table->index('key_prefix');
            $table->foreign('user_id', 'users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
