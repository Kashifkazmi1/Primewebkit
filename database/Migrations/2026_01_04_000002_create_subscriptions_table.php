<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled', 'expired'], false, 'active');
            $table->enum('billing_cycle', ['monthly', 'yearly'], false, 'monthly');
            $table->string('provider', 50, false, 'manual');
            $table->string('provider_customer_id', 191, true);
            $table->string('provider_subscription_id', 191, true);
            $table->dateTime('current_period_start');
            $table->dateTime('current_period_end');
            $table->dateTime('trial_ends_at', true);
            $table->dateTime('grace_period_ends_at', true);
            $table->boolean('cancel_at_period_end', false, false);
            $table->dateTime('canceled_at', true);
            $table->unsignedBigInteger('coupon_id', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->index('user_id');
            $table->index('plan_id');
            $table->index('status');
            $table->index('provider_subscription_id');
            $table->foreign('user_id', 'users', 'id');
            $table->foreign('plan_id', 'plans', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
