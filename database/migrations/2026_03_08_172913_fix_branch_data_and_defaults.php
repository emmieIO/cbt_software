<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['exams', 'users', 'school_classes', 'prospective_classes'];

        foreach ($tables as $table) {
            // 1. Update existing stale data
            DB::table($table)->where('branch', 'primary')->update(['branch' => 'primary_vgc']);
            DB::table($table)->where('branch', 'nursery')->update(['branch' => 'nursery_vgc']);

            // 2. Fix schema default to be a valid enum key
            Schema::table($table, function (Blueprint $table) {
                $table->string('branch')->default('primary_vgc')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to reverse this without losing the specific branching info
    }
};
