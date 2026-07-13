<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('metric', 50); // bots, messages, knowledge_mb, storage_mb, api_requests, ai_requests, team_members
            $table->string('period', 7); // 'YYYY-MM' for monthly-reset metrics, or 'lifetime' for point-in-time gauges
            $table->bigInteger('value', false, 0);
            $table->timestamps();
            $table->unique(['user_id', 'metric', 'period']);
            $table->index('metric');
            $table->foreign('user_id', 'users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
