<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('exams')
            ->where('status', 'scheduled')
            ->update(['status' => 'draft']);
    }

    public function down(): void
    {
        // Intentionally left as a no-op because we cannot safely infer
        // which draft exams were previously marked as scheduled.
    }
};
