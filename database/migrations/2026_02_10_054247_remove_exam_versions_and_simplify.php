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
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreignUlid('exam_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Migrate data if any exists (optional for fresh projects but good practice)
        DB::statement('UPDATE exam_questions eq SET exam_id = (SELECT exam_id FROM exam_versions ev WHERE ev.id = eq.exam_version_id)');

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropForeign(['exam_version_id']);
            $table->dropColumn('exam_version_id');
            $table->foreignUlid('exam_id')->nullable(false)->change();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropForeign(['exam_version_id']);
            $table->dropColumn('exam_version_id');
        });

        Schema::dropIfExists('exam_versions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('exam_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('exam_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->foreignUlid('exam_version_id')->nullable()->constrained()->onDelete('set null');
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreignUlid('exam_version_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->dropColumn('exam_id');
        });
    }
};
