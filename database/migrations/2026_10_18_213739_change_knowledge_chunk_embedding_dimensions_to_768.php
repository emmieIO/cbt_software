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
        $this->clearIncompatibleEmbeddings();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS knowledge_document_chunks_embedding_vectorindex');
            DB::statement('ALTER TABLE knowledge_document_chunks ALTER COLUMN embedding TYPE vector(768) USING NULL::vector(768)');

            Schema::table('knowledge_document_chunks', function (Blueprint $table): void {
                $table->vectorIndex('embedding');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->clearIncompatibleEmbeddings();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS knowledge_document_chunks_embedding_vectorindex');
            DB::statement('ALTER TABLE knowledge_document_chunks ALTER COLUMN embedding TYPE vector(1536) USING NULL::vector(1536)');

            Schema::table('knowledge_document_chunks', function (Blueprint $table): void {
                $table->vectorIndex('embedding');
            });
        }
    }

    private function clearIncompatibleEmbeddings(): void
    {
        DB::table('knowledge_document_chunks')->update([
            'embedding' => null,
            'embedding_provider' => null,
            'embedding_model' => null,
            'embedding_dimensions' => null,
            'embedded_content_hash' => null,
            'embedded_at' => null,
        ]);

        DB::table('knowledge_documents')->update([
            'embedding_status' => 'pending',
            'embedding_provider' => null,
            'embedding_model' => null,
            'embedding_dimensions' => null,
            'embedding_error' => null,
            'embedded_at' => null,
        ]);
    }
};
