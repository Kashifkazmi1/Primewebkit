<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('user_id');
            $table->string('url', 2048);
            $table->string('secret', 64);
            $table->json('events');
            $table->boolean('is_active', false, true);
            $table->dateTime('last_triggered_at', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->index('user_id');
            $table->foreign('user_id', 'users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
