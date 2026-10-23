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
        Schema::create('subtopics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('topic_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('normalized_name');
            $table->timestamps();
            $table->unique(['topic_id', 'normalized_name']);
        });
        Schema::table('curriculum_topic_mappings', function (Blueprint $table) {
            $table->string('classification', 16)->default('topic');
            $table->string('detected_parent_topic')->nullable();
            $table->foreignUlid('subtopic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hierarchy_status', 20)->default('current');
            $table->foreignUlid('merged_into_id')->nullable()->constrained('curriculum_topic_mappings')->nullOnDelete();
            $table->index(['topic_id', 'subtopic_id', 'classification', 'status'], 'curriculum_hierarchy_lookup');
        });
        Schema::create('curriculum_mapping_reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('curriculum_topic_mapping_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->json('before_snapshot');
            $table->json('after_snapshot');
            $table->timestamps();
        });
        Schema::table('question_generation_runs', function (Blueprint $table) {
            $table->foreignUlid('subtopic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subtopic_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_generation_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subtopic_id');
            $table->dropColumn('subtopic_name');
        });
        Schema::dropIfExists('curriculum_mapping_reviews');
        Schema::table('curriculum_topic_mappings', function (Blueprint $table) {
            $table->dropIndex('curriculum_hierarchy_lookup');
            $table->dropConstrainedForeignId('merged_into_id');
            $table->dropConstrainedForeignId('subtopic_id');
            $table->dropColumn(['classification', 'detected_parent_topic', 'hierarchy_status']);
        });
        Schema::dropIfExists('subtopics');
    }
};
