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
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL requires a USING clause to cast from string to json
            DB::statement('ALTER TABLE schools ALTER COLUMN contact_phone TYPE JSON USING contact_phone::json');
        } else {
            // Fallback for SQLite/Testing
            Schema::table('schools', function (Blueprint $table) {
                $table->json('contact_phone')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE schools ALTER COLUMN contact_phone TYPE VARCHAR(255) USING contact_phone::text');
        } else {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('contact_phone')->change();
            });
        }
    }
};
