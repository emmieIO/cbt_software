<?php

use App\Enums\Term;
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
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->string('term')->default(Term::FIRST->value)->after('name');

            // Remove old unique constraint on name
            $table->dropUnique(['name']);

            // Add new unique constraint on name and term
            $table->unique(['name', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropUnique(['name', 'term']);
            $table->unique('name');
            $table->dropColumn('term');
        });
    }
};
