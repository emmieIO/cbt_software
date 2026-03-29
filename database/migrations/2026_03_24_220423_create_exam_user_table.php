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
        // 1. Create the join table for direct student-to-exam assignment
        if (! Schema::hasTable('exam_user')) {
            Schema::create('exam_user', function (Blueprint $table) {
                $table->id();
                $table->char('exam_id', 26)->index();
                $table->char('user_id', 26)->index();
                $table->timestamps();

                $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['exam_id', 'user_id']);
            });
        }

        // 2. Remove batch/prospective class references from core tables safely
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'prospective_class_id')) {
                // Try to drop foreign key if it exists (names vary by driver)
                try {
                    $table->dropForeign(['prospective_class_id']);
                } catch (\Exception $e) {
                }
                $table->dropColumn('prospective_class_id');
            }
            if (Schema::hasColumn('users', 'exam_batch_id')) {
                $table->dropColumn('exam_batch_id');
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'prospective_class_id')) {
                try {
                    $table->dropForeign(['prospective_class_id']);
                } catch (\Exception $e) {
                }
                $table->dropColumn('prospective_class_id');
            }
            if (Schema::hasColumn('exams', 'exam_batch_id')) {
                $table->dropColumn('exam_batch_id');
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'prospective_class_id')) {
                try {
                    $table->dropForeign(['prospective_class_id']);
                } catch (\Exception $e) {
                }
                $table->dropColumn('prospective_class_id');
            }
            if (Schema::hasColumn('questions', 'exam_batch_id')) {
                $table->dropColumn('exam_batch_id');
            }
        });

        Schema::table('teacher_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_assignments', 'prospective_class_id')) {
                // Drop the unique index that includes this column first
                try {
                    $table->dropUnique('teacher_load_unique_full');
                } catch (\Exception $e) {
                }
                try {
                    $table->dropForeign(['prospective_class_id']);
                } catch (\Exception $e) {
                }
                $table->dropColumn('prospective_class_id');
            }
        });

        // 3. Drop the redundant table
        Schema::dropIfExists('exam_batches');
        Schema::dropIfExists('prospective_classes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_user');
    }
};
