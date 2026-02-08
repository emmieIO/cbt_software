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
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('subject_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('school_class_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('academic_session_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // A teacher can only have one assignment for a specific class + subject combo in a session
            $table->unique(['user_id', 'subject_id', 'school_class_id', 'academic_session_id'], 'teacher_load_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
    }
};
