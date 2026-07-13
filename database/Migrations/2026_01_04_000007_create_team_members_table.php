<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['owner', 'admin', 'editor', 'viewer'], false, 'viewer');
            $table->unsignedBigInteger('invited_by', true);
            $table->dateTime('joined_at', false, 'CURRENT_TIMESTAMP');
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
            $table->index('user_id');
            $table->foreign('team_id', 'teams', 'id');
            $table->foreign('user_id', 'users', 'id');
            $table->foreign('invited_by', 'users', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
