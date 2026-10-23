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
            $table->char('content_hash', 64)->nullable()->after('content');
            $table->unsignedInteger('estimated_token_count')->nullable()->after('content_hash');

            $table->index(['knowledge_document_id', 'content_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_document_chunks', function (Blueprint $table) {
            $table->dropIndex(['knowledge_document_id', 'content_hash']);
            $table->dropColumn(['content_hash', 'estimated_token_count']);
        });
    }
};
