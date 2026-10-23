<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('generated_question_drafts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('question_generation_run_id')
                ->constrained('question_generation_runs')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('type', 32);
            $table->string('difficulty', 16);
            $table->text('content');
            $table->text('explanation')->nullable();
            $table->text('expected_answer')->nullable();
            $table->json('options')->nullable();
            $table->json('marking_scheme')->nullable();
            $table->json('source_references');
            $table->json('validation_errors')->nullable();
            $table->json('warnings')->nullable();
            $table->boolean('is_valid')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->unique(['question_generation_run_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_question_drafts');
    }
};
