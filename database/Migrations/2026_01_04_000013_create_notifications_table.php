<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 100);
            $table->string('title', 255);
            $table->text('body', true);
            $table->json('data', true);
            $table->enum('channel', ['in_app', 'email'], false, 'in_app');
            $table->dateTime('read_at', true);
            $table->dateTime('created_at', false, 'CURRENT_TIMESTAMP');
            $table->unique('uuid');
            $table->index('user_id');
            $table->index('read_at');
            $table->foreign('user_id', 'users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
