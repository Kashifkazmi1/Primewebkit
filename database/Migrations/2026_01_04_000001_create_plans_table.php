<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description', true);
            $table->decimal('monthly_price', 10, 2, false, 0);
            $table->decimal('yearly_price', 10, 2, false, 0);
            $table->string('currency', 3, false, 'USD');
            $table->integer('bots_limit', false, 1);
            $table->integer('messages_limit', false, 500);
            $table->integer('knowledge_limit_mb', false, 10);
            $table->integer('storage_limit_mb', false, 100);
            $table->integer('team_members_limit', false, 1);
            $table->boolean('api_access', false, false);
            $table->boolean('analytics', false, false);
            $table->boolean('white_label', false, false);
            $table->boolean('custom_domain', false, false);
            $table->boolean('priority_support', false, false);
            $table->boolean('streaming', false, true);
            $table->integer('trial_days', false, 0);
            $table->boolean('is_active', false, true);
            $table->integer('sort_order', false, 0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('uuid');
            $table->unique('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
