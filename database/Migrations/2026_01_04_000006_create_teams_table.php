<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('owner_id');
            $table->string('name', 150);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('uuid');
            $table->index('owner_id');
            $table->foreign('owner_id', 'users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
