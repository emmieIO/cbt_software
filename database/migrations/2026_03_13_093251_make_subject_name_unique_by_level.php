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
        Schema::table('subjects', function (Blueprint $table) {
            // Drop old slug unique index as it will now include level
            $table->dropUnique('subjects_slug_unique');
            
            // Add composite unique index for name and level
            $table->unique(['name', 'level']);
            
            // Re-add slug unique (slug will be level-aware now)
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique(['name', 'level']);
        });
    }
};
