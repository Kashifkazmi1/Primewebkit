<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('bot_id');
            $table->enum('type', ['document', 'website', 'text', 'qa']);
            $table->string('source_name', 255);
            $table->string('source_url', 2048, true);
            $table->string('file_path', 255, true);
            $table->longText('raw_text', true);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'], false, 'pending');
            $table->integer('character_count', true);
            $table->integer('chunk_count', false, 0);
            $table->text('error_message', true);
            $table->dateTime('processed_at', true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('uuid');
            $table->index('bot_id');
            $table->index('status');
            $table->foreign('bot_id', 'bots', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_sources');
    }
};
