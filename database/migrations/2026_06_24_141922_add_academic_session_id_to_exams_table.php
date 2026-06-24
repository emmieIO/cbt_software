<?php

use Carbon\CarbonInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignUlid('academic_session_id')
                ->nullable()
                ->after('title')
                ->constrained('academic_sessions')
                ->restrictOnDelete();
        });

        $now = now();
        $currentSessionName = $this->sessionName($now);
        $sessionNames = DB::table('exams')
            ->select('created_at')
            ->orderBy('created_at')
            ->get()
            ->map(fn (object $exam): string => $this->sessionName(Carbon::parse($exam->created_at)))
            ->push($currentSessionName)
            ->unique()
            ->values();

        foreach ($sessionNames as $sessionName) {
            [$startYear, $endYear] = array_map('intval', explode('/', $sessionName));

            DB::table('academic_sessions')->insert([
                'id' => (string) Str::ulid(),
                'name' => $sessionName,
                'starts_at' => "{$startYear}-09-01",
                'ends_at' => "{$endYear}-08-31",
                'is_active' => $sessionName === $currentSessionName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('exams')
            ->select(['id', 'created_at'])
            ->orderBy('created_at')
            ->get()
            ->each(function (object $exam): void {
                $sessionId = DB::table('academic_sessions')
                    ->where('name', $this->sessionName(Carbon::parse($exam->created_at)))
                    ->value('id');

                DB::table('exams')
                    ->where('id', $exam->id)
                    ->update(['academic_session_id' => $sessionId]);
            });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_session_id');
        });
    }

    private function sessionName(CarbonInterface $date): string
    {
        $startYear = $date->month >= 9 ? $date->year : $date->year - 1;

        return "{$startYear}/".($startYear + 1);
    }
};
