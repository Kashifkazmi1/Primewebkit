<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('user_id', true);
            $table->string('action', 100);
            $table->string('auditable_type', 150, true);
            $table->unsignedBigInteger('auditable_id', true);
            $table->string('ip_address', 45, true);
            $table->string('user_agent', 255, true);
            $table->json('metadata', true);
            $table->dateTime('created_at', false, 'CURRENT_TIMESTAMP');
            $table->unique('uuid');
            $table->index('user_id');
            $table->index('action');
            $table->index(['auditable_type', 'auditable_id']);
            $table->foreign('user_id', 'users', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
