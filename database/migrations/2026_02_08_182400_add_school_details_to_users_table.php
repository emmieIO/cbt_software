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
            $table->string('school_id')->nullable()->unique()->after('id');
            $table->foreignUlid('school_class_id')->nullable()->constrained('school_classes')->onDelete('set null')->after('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_class_id']);
            $table->dropColumn(['school_id', 'school_class_id']);
        });
    }
};
