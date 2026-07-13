<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('team_id');
            $table->string('email', 190);
            $table->enum('role', ['admin', 'editor', 'viewer'], false, 'viewer');
            $table->string('token_hash', 255);
            $table->unsignedBigInteger('invited_by');
            $table->dateTime('expires_at');
            $table->dateTime('accepted_at', true);
            $table->dateTime('revoked_at', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->unique('token_hash');
            $table->index('team_id');
            $table->index('email');
            $table->foreign('team_id', 'teams', 'id');
            $table->foreign('invited_by', 'users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
