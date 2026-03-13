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
            $table->dropUnique('users_school_id_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('school_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('school_id')->nullable()->constrained('schools')->onDelete('set null')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('school_id')->nullable()->unique()->after('id');
        });
    }
};
