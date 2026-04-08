<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('exam:normalize-attempt-scores {--dry-run : Preview mismatches without updating}', function () {
    $computedScores = DB::table('exam_answers')
        ->select('exam_attempt_id', DB::raw('COALESCE(SUM(CASE WHEN is_correct THEN 1 ELSE 0 END), 0) as computed_score'))
        ->groupBy('exam_attempt_id');

    $mismatches = DB::table('exam_attempts as ea')
        ->leftJoinSub($computedScores, 'calc', fn ($join) => $join->on('calc.exam_attempt_id', '=', 'ea.id'))
        ->select(
            'ea.id',
            DB::raw('ea.score as stored_score'),
            DB::raw('COALESCE(calc.computed_score, 0) as computed_score')
        )
        ->whereRaw('ABS(ea.score - COALESCE(calc.computed_score, 0)) > 0.0001')
        ->orderBy('ea.created_at')
        ->get();

    if ($mismatches->isEmpty()) {
        $this->info('No irregular scores found. Database is already consistent.');

        return self::SUCCESS;
    }

    $this->warn("Found {$mismatches->count()} attempt(s) with irregular score values.");
    $this->table(
        ['Attempt ID', 'Stored', 'Computed'],
        $mismatches->take(20)->map(fn ($row) => [$row->id, $row->stored_score, $row->computed_score])->toArray()
    );

    if ($this->option('dry-run')) {
        $this->line('Dry run only: no records were updated.');

        return self::SUCCESS;
    }

    DB::transaction(function () use ($mismatches) {
        foreach ($mismatches as $row) {
            DB::table('exam_attempts')
                ->where('id', $row->id)
                ->update([
                    'score' => $row->computed_score,
                    'updated_at' => now(),
                ]);
        }
    });

    $this->info("Updated {$mismatches->count()} attempt score(s) successfully.");

    return self::SUCCESS;
})->purpose('Normalize exam_attempts.score from count of correct exam_answers.');
