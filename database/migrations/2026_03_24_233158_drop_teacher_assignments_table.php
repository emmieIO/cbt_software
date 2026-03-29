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
        Schema::dropIfExists('teacher_assignments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('user_id', 26)->index();
            $table->char('subject_id', 26)->nullable();
            $table->char('school_class_id', 26)->nullable();
            $table->char('academic_session_id', 26);
            $table->timestamps();
        });
    }
};
