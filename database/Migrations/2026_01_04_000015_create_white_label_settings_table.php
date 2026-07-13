<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('white_label_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('logo_path', 255, true);
            $table->string('primary_color', 20, true);
            $table->string('secondary_color', 20, true);
            $table->string('custom_domain', 255, true);
            $table->boolean('remove_branding', false, false);
            $table->timestamps();
            $table->unique('user_id');
            $table->foreign('user_id', 'users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_settings');
    }
};
