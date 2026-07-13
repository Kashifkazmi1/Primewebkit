<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('webhook_id');
            $table->string('event', 100);
            $table->json('payload');
            $table->integer('response_status', true);
            $table->text('response_body', true);
            $table->integer('attempt', false, 1);
            $table->enum('status', ['pending', 'success', 'failed'], false, 'pending');
            $table->dateTime('created_at', false, 'CURRENT_TIMESTAMP');
            $table->unique('uuid');
            $table->index('webhook_id');
            $table->index('event');
            $table->index('status');
            $table->foreign('webhook_id', 'webhooks', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
