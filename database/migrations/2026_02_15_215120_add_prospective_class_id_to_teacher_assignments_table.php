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
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->foreignUlid('prospective_class_id')->nullable()->after('school_class_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('school_class_id')->nullable()->change();

            // Re-evaluate unique constraint
            $table->dropUnique('teacher_load_unique');
            $table->unique(['user_id', 'subject_id', 'school_class_id', 'prospective_class_id', 'academic_session_id'], 'teacher_load_unique_full');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->dropUnique('teacher_load_unique_full');
            $table->dropForeign(['prospective_class_id']);
            $table->dropColumn('prospective_class_id');
            $table->foreignUlid('school_class_id')->nullable(false)->change();
            $table->unique(['user_id', 'subject_id', 'school_class_id', 'academic_session_id'], 'teacher_load_unique');
        });
    }
};
