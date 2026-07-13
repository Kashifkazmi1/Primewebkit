<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('invoice_id', true);
            $table->enum('type', ['charge', 'refund'], false, 'charge');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3, false, 'USD');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'], false, 'pending');
            $table->string('provider', 50, false, 'manual');
            $table->string('provider_transaction_id', 191, true);
            $table->text('failure_reason', true);
            $table->json('metadata', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->index('user_id');
            $table->index('invoice_id');
            $table->index('status');
            $table->foreign('user_id', 'users', 'id');
            $table->foreign('invoice_id', 'invoices', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
