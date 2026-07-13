<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
            $table->foreign('user_id', 'users', 'id');
            $table->foreign('role_id', 'roles', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
