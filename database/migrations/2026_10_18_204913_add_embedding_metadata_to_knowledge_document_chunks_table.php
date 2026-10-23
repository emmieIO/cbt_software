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
        Schema::table('knowledge_document_chunks', function (Blueprint $table) {
            $table->string('embedding_provider')->nullable()->after('embedding');
            $table->string('embedding_model')->nullable()->after('embedding_provider');
            $table->unsignedSmallInteger('embedding_dimensions')->nullable()->after('embedding_model');
            $table->char('embedded_content_hash', 64)->nullable()->after('embedding_dimensions');
            $table->timestamp('embedded_at')->nullable()->after('embedded_content_hash');

            $table->index(
                ['embedding_provider', 'embedding_model', 'embedding_dimensions'],
                'knowledge_chunks_embedding_configuration_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_document_chunks', function (Blueprint $table) {
            $table->dropIndex('knowledge_chunks_embedding_configuration_index');
            $table->dropColumn([
                'embedding_provider',
                'embedding_model',
                'embedding_dimensions',
                'embedded_content_hash',
                'embedded_at',
            ]);
        });
    }
};
