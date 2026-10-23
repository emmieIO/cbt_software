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
        Schema::create('curriculum_topic_mappings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('knowledge_document_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('class_level', 2)->nullable();
            $table->string('detected_subject')->nullable();
            $table->string('detected_class', 2)->nullable();
            $table->string('detected_topic');
            $table->string('section_heading')->nullable();
            $table->char('identity_key', 64);
            $table->unsignedInteger('chunk_version');
            $table->string('status', 16)->default('suggested');
            $table->string('evidence_status', 20)->default('current');
            $table->string('provenance', 16)->default('ai');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['knowledge_document_id', 'identity_key']);
            $table->index(['subject_id', 'class_level', 'topic_id', 'status'], 'curriculum_coverage_lookup');
        });

        Schema::create('curriculum_topic_evidence', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('curriculum_topic_mapping_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('knowledge_document_chunk_id')->nullable()->constrained()->nullOnDelete();
            $table->char('content_hash', 64);
            $table->text('excerpt');
            $table->unsignedInteger('page_start')->nullable();
            $table->unsignedInteger('page_end')->nullable();
            $table->timestamps();
            $table->unique(['curriculum_topic_mapping_id', 'knowledge_document_chunk_id'], 'curriculum_evidence_unique');
        });

        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->string('topic_detection_status', 16)->default('pending');
            $table->text('topic_detection_error')->nullable();
            $table->unsignedInteger('topic_detection_version')->nullable();
            $table->unsignedInteger('topic_detection_cursor')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->dropColumn(['topic_detection_status', 'topic_detection_error', 'topic_detection_version', 'topic_detection_cursor']);
        });
        Schema::dropIfExists('curriculum_topic_evidence');
        Schema::dropIfExists('curriculum_topic_mappings');
    }
};
