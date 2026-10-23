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
        Schema::create('knowledge_document_chunks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('knowledge_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->text('content');
            $table->json('metadata')->nullable();
            if (DB::getDriverName() === 'pgsql') {
                $table->vector('embedding', dimensions: 1536)->nullable();
            } elseif (DB::getDriverName() === 'sqlite' && app()->runningUnitTests()) {
                $table->json('embedding')->nullable();
            } else {
                throw new RuntimeException('Knowledge document vectors require PostgreSQL.');
            }
            $table->timestamps();

            $table->unique(['knowledge_document_id', 'sequence']);
        });

        if (DB::getDriverName() === 'pgsql') {
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
        Schema::dropIfExists('knowledge_document_chunks');
    }
};
