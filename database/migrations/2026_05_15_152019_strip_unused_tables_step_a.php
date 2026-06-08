<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('questions', 'school_class_id')) {
            Schema::table('questions', fn ($t) => $t->dropConstrainedForeignId('school_class_id'));
        }

        if (Schema::hasColumn('topics', 'school_class_id')) {
            Schema::table('topics', fn ($t) => $t->dropConstrainedForeignId('school_class_id'));
        }

        if (Schema::hasColumn('users', 'school_id')) {
            Schema::table('users', fn ($t) => $t->dropConstrainedForeignId('school_id'));
        }

        if (Schema::hasColumn('users', 'school_class_id')) {
            Schema::table('users', fn ($t) => $t->dropConstrainedForeignId('school_class_id'));
        }

        Schema::dropIfExists('agent_conversation_messages');
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_compositions');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exam_user');
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('school_user');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('academic_sessions');
        Schema::dropIfExists('agent_conversations');
        Schema::dropIfExists('schools');
    }

    public function down(): void {}
};
