<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('role_id');
            $table->string('name', 150);
            $table->string('email', 190);
            $table->dateTime('email_verified_at', true);
            $table->string('password', 255);
            $table->enum('status', ['active', 'suspended', 'pending'], false, 'pending');
            $table->string('avatar_path', 255, true);
            $table->string('timezone', 64, false, 'UTC');
            $table->string('locale', 10, false, 'en');
            $table->integer('failed_login_attempts', false, 0);
            $table->dateTime('locked_until', true);
            $table->dateTime('last_login_at', true);
            $table->string('last_login_ip', 45, true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('uuid');
            $table->unique('email');
            $table->index('role_id');
            $table->index('status');
            $table->foreign('role_id', 'roles', 'id', 'RESTRICT');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
