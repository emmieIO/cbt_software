<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('subject_name');
            $table->string('level', 2);
            $table->text('instructions')->nullable();
            $table->integer('mcq_count')->default(0);
            $table->integer('theory_count')->default(0);
            $table->integer('total_marks')->default(0);
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('exam_question', function (Blueprint $table) {
            $table->foreignUlid('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('question_id')->constrained()->cascadeOnDelete();
            $table->string('section', 10); // 'mcq' or 'theory'
            $table->integer('sort_order')->default(0);
            $table->unique(['exam_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_question');
        Schema::dropIfExists('exams');
    }
};
