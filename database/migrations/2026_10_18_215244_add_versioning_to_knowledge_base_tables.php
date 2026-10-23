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
        Schema::table('knowledge_documents', function (Blueprint $table): void {
            $table->unsignedInteger('active_chunk_version')->default(1)->after('embedding_status');
            $table->unsignedInteger('pending_chunk_version')->nullable()->after('active_chunk_version');
            $table->string('reprocessing_status')->nullable()->index()->after('pending_chunk_version');
            $table->text('reprocessing_error')->nullable()->after('reprocessing_status');
        });

        Schema::table('knowledge_document_chunks', function (Blueprint $table): void {
            $table->dropUnique(['knowledge_document_id', 'sequence']);
            $table->unsignedInteger('chunk_version')->default(1)->after('knowledge_document_id');
            $table->unique(
                ['knowledge_document_id', 'chunk_version', 'sequence'],
                'knowledge_chunks_document_version_sequence_unique',
            );
            $table->index(
                ['knowledge_document_id', 'chunk_version'],
                'knowledge_chunks_document_version_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_document_chunks', function (Blueprint $table): void {
            $table->dropUnique('knowledge_chunks_document_version_sequence_unique');
            $table->dropIndex('knowledge_chunks_document_version_index');
            $table->dropColumn('chunk_version');
            $table->unique(['knowledge_document_id', 'sequence']);
        });

        Schema::table('knowledge_documents', function (Blueprint $table): void {
            $table->dropIndex(['reprocessing_status']);
            $table->dropColumn([
                'active_chunk_version',
                'pending_chunk_version',
                'reprocessing_status',
                'reprocessing_error',
            ]);
        });
    }
};
