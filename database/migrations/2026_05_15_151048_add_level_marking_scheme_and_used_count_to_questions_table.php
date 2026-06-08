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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('level', 2)->nullable()->after('content');
            $table->json('marking_scheme')->nullable()->after('explanation');
            $table->integer('used_count')->default(0)->after('marking_scheme');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['level', 'marking_scheme', 'used_count']);
        });
    }
};
