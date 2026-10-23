<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('subtopics') && Schema::hasTable('curriculum_mapping_reviews')
            && Schema::hasColumn('curriculum_topic_mappings', 'classification')
            && Schema::hasColumn('question_generation_runs', 'subtopic_id')) {
            return;
        }
        if (Schema::hasTable('subtopics') || Schema::hasTable('curriculum_mapping_reviews')
            || Schema::hasColumn('curriculum_topic_mappings', 'classification')
            || Schema::hasColumn('question_generation_runs', 'subtopic_id')) {
            throw new RuntimeException('Partial curriculum hierarchy schema found. Inspect it before applying repairs.');
        }

        $foundation = require __DIR__.'/2026_10_18_225810_add_curriculum_topic_hierarchy.php';
        $foundation->up();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The foundation migration owns the schema and its rollback.
    }
};
