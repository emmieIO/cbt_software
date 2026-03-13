<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL requires a USING clause to cast from string to json
        DB::statement('ALTER TABLE schools ALTER COLUMN contact_phone TYPE JSON USING contact_phone::json');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE schools ALTER COLUMN contact_phone TYPE VARCHAR(255) USING contact_phone::text');
    }
};
