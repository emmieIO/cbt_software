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
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->string('embedding_status')->default('pending')->index()->after('approval_status');
            $table->string('embedding_provider')->nullable()->after('processing_error');
            $table->string('embedding_model')->nullable()->after('embedding_provider');
            $table->unsignedSmallInteger('embedding_dimensions')->nullable()->after('embedding_model');
            $table->text('embedding_error')->nullable()->after('embedding_dimensions');
            $table->timestamp('embedded_at')->nullable()->after('embedding_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->dropColumn([
                'embedding_status',
                'embedding_provider',
                'embedding_model',
                'embedding_dimensions',
                'embedding_error',
                'embedded_at',
            ]);
        });
    }
};
