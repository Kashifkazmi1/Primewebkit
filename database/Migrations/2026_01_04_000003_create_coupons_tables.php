<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\AlterBlueprint;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('code', 50);
            $table->enum('type', ['percent', 'fixed'], false, 'percent');
            $table->decimal('value', 10, 2);
            $table->integer('max_redemptions', true);
            $table->integer('times_redeemed', false, 0);
            $table->dateTime('valid_from', true);
            $table->dateTime('valid_until', true);
            $table->boolean('is_active', false, true);
            $table->timestamps();
            $table->unique('uuid');
            $table->unique('code');
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subscription_id', true);
            $table->dateTime('redeemed_at', false, 'CURRENT_TIMESTAMP');
            $table->index('coupon_id');
            $table->index('user_id');
            $table->foreign('coupon_id', 'coupons', 'id');
            $table->foreign('user_id', 'users', 'id');
            $table->foreign('subscription_id', 'subscriptions', 'id', 'SET NULL');
        });

        Schema::table('subscriptions', function (AlterBlueprint $table) {
            $table->foreign('coupon_id', 'coupons', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
