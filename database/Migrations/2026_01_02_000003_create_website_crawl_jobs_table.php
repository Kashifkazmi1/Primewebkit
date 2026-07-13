<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('website_crawl_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('knowledge_source_id');
            $table->string('start_url', 2048);
            $table->enum('status', ['queued', 'crawling', 'completed', 'failed'], false, 'queued');
            $table->integer('max_pages', false, 20);
            $table->integer('pages_found', false, 0);
            $table->integer('pages_processed', false, 0);
            $table->json('discovered_urls', true);
            $table->text('error_message', true);
            $table->dateTime('started_at', true);
            $table->dateTime('completed_at', true);
            $table->timestamps();
            $table->unique('uuid');
            $table->index('knowledge_source_id');
            $table->index('status');
            $table->foreign('knowledge_source_id', 'knowledge_sources', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_crawl_jobs');
    }
};
