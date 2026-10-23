<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('curriculum_topic_evidence', function (Blueprint $table) {
            $table->index(['curriculum_topic_mapping_id', 'knowledge_document_chunk_id'], 'curriculum_evidence_lookup');
            $table->dropUnique('curriculum_evidence_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('curriculum_topic_evidence')->select('curriculum_topic_mapping_id', 'knowledge_document_chunk_id')
            ->whereNotNull('knowledge_document_chunk_id')->groupBy('curriculum_topic_mapping_id', 'knowledge_document_chunk_id')
            ->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot restore one-passage-per-chunk uniqueness without losing reviewed evidence.');
        }
        Schema::table('curriculum_topic_evidence', function (Blueprint $table) {
            $table->unique(['curriculum_topic_mapping_id', 'knowledge_document_chunk_id'], 'curriculum_evidence_unique');
            $table->dropIndex('curriculum_evidence_lookup');
        });
    }
};
