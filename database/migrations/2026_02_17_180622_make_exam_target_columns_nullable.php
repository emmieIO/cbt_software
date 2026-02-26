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
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignUlid('subject_id')->nullable()->change();
            $table->foreignUlid('school_class_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Reverting to non-nullable might fail if null data exists,
            // but standard Laravel practice is to define it.
            $table->foreignUlid('subject_id')->nullable(false)->change();
            $table->foreignUlid('school_class_id')->nullable(false)->change();
        });
    }
};
