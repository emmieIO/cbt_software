<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite' && app()->runningUnitTests()) {
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException('The knowledge base requires PostgreSQL with the pgvector extension available.');
        }

        $extension = DB::selectOne("select name from pg_available_extensions where name = 'vector'");

        if ($extension === null) {
            throw new RuntimeException(
                'The PostgreSQL pgvector server package is not installed. Install it before running this migration.'
            );
        }

        Schema::ensureVectorExtensionExists();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The extension may be shared by other tables, so rollback leaves it installed.
    }
};
