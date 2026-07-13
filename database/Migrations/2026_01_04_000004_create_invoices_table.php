<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subscription_id', true);
            $table->string('invoice_number', 50);
            $table->decimal('subtotal', 10, 2, false, 0);
            $table->decimal('discount_amount', 10, 2, false, 0);
            $table->decimal('tax_amount', 10, 2, false, 0);
            $table->decimal('total', 10, 2, false, 0);
            $table->string('currency', 3, false, 'USD');
            $table->enum('status', ['draft', 'open', 'paid', 'void', 'uncollectible'], false, 'draft');
            $table->dateTime('due_date', true);
            $table->dateTime('paid_at', true);
            $table->string('provider', 50, false, 'manual');
            $table->string('provider_invoice_id', 191, true);
            $table->json('line_items', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->unique('invoice_number');
            $table->index('user_id');
            $table->index('subscription_id');
            $table->index('status');
            $table->foreign('user_id', 'users', 'id');
            $table->foreign('subscription_id', 'subscriptions', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
