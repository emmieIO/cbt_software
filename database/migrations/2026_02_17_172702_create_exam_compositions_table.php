<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_compositions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('exam_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('subject_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('topic_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('question_count')->default(1);
            $table->decimal('marks_per_question', 5, 2)->default(1.00);
            $table->timestamps();
        });

        // Also make subject_id nullable on exams table for multi-subject assessments
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignUlid('subject_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_compositions');
    }
};
