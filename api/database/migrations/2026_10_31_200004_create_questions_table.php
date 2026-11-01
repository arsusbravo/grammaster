<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->restrictOnDelete();
            $table->foreignId('level_id')->constrained()->restrictOnDelete();
            $table->string('type', 32)->index();
            $table->text('prompt');
            // Type-specific content (see App\Support\QuestionData); new types need no migration.
            $table->json('data');
            $table->text('explanation');
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'topic_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
