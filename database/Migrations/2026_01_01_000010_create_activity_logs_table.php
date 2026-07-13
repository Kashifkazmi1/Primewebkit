<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id', true);
            $table->string('description', 255);
            $table->string('subject_type', 150, true);
            $table->unsignedBigInteger('subject_id', true);
            $table->json('properties', true);
            $table->dateTime('created_at', false, 'CURRENT_TIMESTAMP');
            $table->index('user_id');
            $table->index(['subject_type', 'subject_id']);
            $table->foreign('user_id', 'users', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
