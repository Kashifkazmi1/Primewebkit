<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description', true);
            $table->boolean('is_system', false, false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
