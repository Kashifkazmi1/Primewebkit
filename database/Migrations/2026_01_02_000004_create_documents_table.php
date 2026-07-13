<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;
use App\Core\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->unsignedBigInteger('bot_id');
            $table->unsignedBigInteger('knowledge_source_id');
            $table->integer('chunk_index', false, 0);
            $table->longText('content');
            $table->integer('token_count', true);
            $table->longText('embedding', true);
            $table->string('embedding_model', 100, true);
            $table->timestamps();
            $table->unique('uuid');
            $table->index('bot_id');
            $table->index('knowledge_source_id');
            $table->foreign('bot_id', 'bots', 'id');
            $table->foreign('knowledge_source_id', 'knowledge_sources', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
