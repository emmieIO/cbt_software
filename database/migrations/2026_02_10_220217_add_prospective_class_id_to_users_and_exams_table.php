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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('prospective_class_id')->nullable()->after('school_class_id')->constrained('prospective_classes')->nullOnDelete();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->foreignUlid('prospective_class_id')->nullable()->after('school_class_id')->constrained('prospective_classes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['prospective_class_id']);
            $table->dropColumn('prospective_class_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['prospective_class_id']);
            $table->dropColumn('prospective_class_id');
        });
    }
};