<?php

namespace App\Services;

use App\DTOs\ExamDTO;
use App\DTOs\ExamVersionDTO;
use App\Models\Exam;
use App\Models\ExamVersion;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExamService implements ExamServiceInterface
{
    /**
     * Create a new exam.
     */
    public function createExam(ExamDTO $dto, string $creatorId): Exam
    {
        return DB::transaction(function () use ($dto, $creatorId) {
            return Exam::create([
                ...$dto->toArray(),
                'created_by' => $creatorId,
                'status' => 'draft',
            ]);
        });
    }

    /**
     * Create a new version for an exam.
     */
    public function createVersion(Exam $exam, ExamVersionDTO $dto): ExamVersion
    {
        return $exam->versions()->create($dto->toArray());
    }

    /**
     * Delete an exam version.
     */
    public function deleteVersion(ExamVersion $version): void
    {
        $version->delete();
    }

    /**
     * Update questions for a specific exam version.
     */
    public function updateVersionQuestions(ExamVersion $version, array $questionIds): void
    {
        $version->questions()->sync($questionIds);
    }

    /**
     * Automatically select questions for a version using the biennial rotation policy.
     *
     * Policy:
     * 1. Prioritize questions never used or used > 2 years ago.
     * 2. Fallback to Least Recently Used (LRU) if quota not met.
     */
    public function autoSelectQuestions(Exam $exam, ExamVersion $version, int $count): int
    {
        return DB::transaction(function () use ($exam, $version, $count) {
            $twoYearsAgo = now()->subYears(2);

            // 1. Primary Pool: Compliant questions (Never used OR used > 2 years ago)
            $primaryPool = Question::whereHas('topic', fn($q) => $q->where('subject_id', $exam->subject_id))
                ->where('school_class_id', $exam->school_class_id)
                ->where('is_active', true)
                ->where(function ($query) use ($twoYearsAgo) {
                    $query->whereNull('last_used_at')
                          ->orWhere('last_used_at', '<', $twoYearsAgo);
                })
                ->inRandomOrder()
                ->limit($count)
                ->get();

            $selectedQuestions = $primaryPool;

            // 2. Secondary Pool: Fill gap with LRU questions if needed
            if ($selectedQuestions->count() < $count) {
                $remainingNeeded = $count - $selectedQuestions->count();
                
                $secondaryPool = Question::whereHas('topic', fn($q) => $q->where('subject_id', $exam->subject_id))
                    ->where('school_class_id', $exam->school_class_id)
                    ->where('is_active', true)
                    ->whereNotIn('id', $selectedQuestions->pluck('id'))
                    ->orderBy('last_used_at', 'asc') // Oldest usage first
                    ->limit($remainingNeeded)
                    ->get();

                $selectedQuestions = $selectedQuestions->concat($secondaryPool);
            }

            // Sync selected questions
            $version->questions()->sync($selectedQuestions->pluck('id'));

            // Update usage timestamps
            if ($selectedQuestions->isNotEmpty()) {
                Question::whereIn('id', $selectedQuestions->pluck('id'))
                    ->update(['last_used_at' => now()]);
            }

            return $selectedQuestions->count();
        });
    }

    /**
     * Get available questions for an exam context.
     */
    public function getAvailableQuestions(Exam $exam): Collection
    {
        return Question::whereHas('topic', fn($q) => $q->where('subject_id', $exam->subject_id))
            ->where('school_class_id', $exam->school_class_id)
            ->where('is_active', true)
            ->with('topic')
            ->get();
    }
}
