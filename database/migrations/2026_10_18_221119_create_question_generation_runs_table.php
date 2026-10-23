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
        Schema::create('question_generation_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('subject_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('topic_id')->constrained()->restrictOnDelete();
            $table->string('level', 2);
            $table->string('class_level', 2);
            $table->unsignedSmallInteger('requested_count');
            $table->json('type_distribution');
            $table->string('difficulty', 16);
            $table->text('additional_instructions')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->text('retrieval_query')->nullable();
            $table->json('source_references')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['requested_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_generation_runs');
    }
};
