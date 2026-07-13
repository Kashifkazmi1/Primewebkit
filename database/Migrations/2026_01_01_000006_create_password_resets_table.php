<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190);
            $table->string('token_hash', 255);
            $table->dateTime('expires_at');
            $table->dateTime('used_at', true);
            $table->timestamps();
            $table->index('email');
            $table->unique('token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
