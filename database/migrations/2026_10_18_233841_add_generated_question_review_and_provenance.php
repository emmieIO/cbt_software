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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('origin', 24)->nullable()->index();
            $table->string('difficulty', 16)->nullable();
            $table->text('expected_answer')->nullable();
            $table->foreignUlid('subtopic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('generated_draft_id')->nullable()->unique()->constrained('generated_question_drafts')->restrictOnDelete();
        });
        Schema::table('generated_question_drafts', function (Blueprint $table) {
            $table->string('review_status', 16)->default('draft')->index();
            $table->unsignedInteger('revision')->default(1);
            $table->foreignUlid('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('approved_hash', 64)->nullable();
            $table->boolean('answers_verified')->default(false);
            $table->foreignUlid('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->foreignUlid('question_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->json('import_errors')->nullable();
        });
        Schema::table('question_generation_runs', function (Blueprint $table) {
            $table->unsignedInteger('generation_attempt')->default(0);
            $table->string('import_status', 24)->default('not_imported');
            $table->json('last_import_summary')->nullable();
        });
        Schema::create('generated_draft_reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('generated_question_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 24);
            $table->json('snapshot');
            $table->timestamps();
        });
        Schema::create('question_provenances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('question_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('question_generation_run_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('generated_question_draft_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('knowledge_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_identity', 26);
            $table->string('document_title');
            $table->string('original_name')->nullable();
            $table->string('document_checksum', 64)->nullable();
            $table->string('chunk_identity', 26);
            $table->unsignedInteger('chunk_version');
            $table->string('content_hash', 64);
            $table->unsignedInteger('page_start')->nullable();
            $table->unsignedInteger('page_end')->nullable();
            $table->foreignUlid('curriculum_topic_mapping_id')->nullable()->constrained()->nullOnDelete();
            $table->json('curriculum_snapshot');
            $table->timestamps();
            $table->unique(['question_id', 'chunk_identity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_provenances');
        Schema::dropIfExists('generated_draft_reviews');
        Schema::table('question_generation_runs', function (Blueprint $table) {
            $table->dropColumn(['import_status', 'last_import_summary', 'generation_attempt']);
        });
        Schema::table('generated_question_drafts', function (Blueprint $table) {
            foreach (['question_id', 'edited_by', 'reviewed_by', 'imported_by'] as $column) {
                $table->dropConstrainedForeignId($column);
            }
            $table->dropColumn(['review_status', 'revision', 'reviewed_at', 'approved_hash', 'answers_verified', 'imported_at', 'import_errors']);
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subtopic_id');
            $table->dropConstrainedForeignId('generated_draft_id');
            $table->dropColumn(['origin', 'difficulty', 'expected_answer']);
        });
    }
};
