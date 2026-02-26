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
        Schema::table('exam_compositions', function (Blueprint $table) {
            $table->foreignUlid('source_class_id')->nullable()->constrained('school_classes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_compositions', function (Blueprint $table) {
            $table->dropForeign(['source_class_id']);
            $table->dropColumn('source_class_id');
        });
    }
};
