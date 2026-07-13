<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cron_job_runs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name', 100);
            $table->enum('status', ['running', 'success', 'failed'], false, 'running');
            $table->dateTime('started_at');
            $table->dateTime('finished_at', true);
            $table->text('output', true);
            $table->timestamps();
            $table->index('job_name');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_job_runs');
    }
};
