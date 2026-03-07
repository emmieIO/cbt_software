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
            $table->string('branch')->default('primary_vgc')->after('school_class_id');
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->string('branch')->default('primary_vgc')->after('level');
        });

        Schema::table('prospective_classes', function (Blueprint $table) {
            $table->string('branch')->default('primary_vgc')->after('pass_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('branch');
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropColumn('branch');
        });

        Schema::table('prospective_classes', function (Blueprint $table) {
            $table->dropColumn('branch');
        });
    }
};
