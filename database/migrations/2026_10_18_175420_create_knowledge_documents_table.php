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
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('document_type')->index();
            $table->text('description')->nullable();
            $table->string('original_name');
            $table->string('disk')->default('local');
            $table->string('path')->unique();
            $table->string('mime_type');
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum', 64)->index();
            $table->string('processing_status')->default('pending')->index();
            $table->string('approval_status')->default('pending')->index();
            $table->text('processing_error')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignUlid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
    }
};
