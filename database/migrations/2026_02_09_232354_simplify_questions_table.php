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
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'version', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->ulid('parent_id')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            
            $table->foreign('parent_id')
                ->references('id')
                ->on('questions')
                ->nullOnDelete();
        });
    }
};
