<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table): void {
            $table->string('class_level', 2)->nullable()->after('subject_id')->index();
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->string('class_level', 2)->nullable()->after('level')->index();
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->string('class_level', 2)->nullable()->after('level')->index();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn('class_level');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn('class_level');
        });

        Schema::table('topics', function (Blueprint $table): void {
            $table->dropColumn('class_level');
        });
    }
};
