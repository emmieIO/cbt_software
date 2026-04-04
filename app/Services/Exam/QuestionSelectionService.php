<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\Question;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class QuestionSelectionService
{
    /**
     * Automatically select questions for an exam using the biennial rotation policy.
     */
    public function autoSelectQuestions(Exam $exam, ?int $totalCount = null): int
    {
        return DB::transaction(function () use ($exam, $totalCount) {
            $twoYearsAgo = now()->subYears(2);
            $selectedQuestionIds = [];

            if ($exam->compositions()->exists()) {
                foreach ($exam->compositions as $composition) {
                    $sectionIds = $this->pullQuestionsForCriteria(
                        subjectId: $composition->subject_id,
                        topicId: $composition->topic_id,
                        schoolClassId: $composition->source_class_id ?? $exam->school_class_id,
                        count: $composition->question_count,
                        twoYearsAgo: $twoYearsAgo
                    );
                    $selectedQuestionIds = array_merge($selectedQuestionIds, $sectionIds);
                }
            } elseif ($totalCount && $exam->subject_id) {
                $selectedQuestionIds = $this->pullQuestionsForCriteria(
                    subjectId: $exam->subject_id,
                    topicId: null,
                    schoolClassId: $exam->school_class_id,
                    count: $totalCount,
                    twoYearsAgo: $twoYearsAgo
                );
            }

            if (! empty($selectedQuestionIds)) {
                $exam->questions()->sync($selectedQuestionIds);
                Question::whereIn('id', $selectedQuestionIds)->update(['last_used_at' => now()]);
            }

            return count($selectedQuestionIds);
        });
    }

    /**
     * Get available questions for an exam context.
     */
    public function getAvailableQuestions(Exam $exam): Collection
    {
        $query = Question::query();

        if ($exam->compositions()->exists()) {
            $subjectIds = $exam->compositions->pluck('subject_id')->unique();
            $query->whereHas('topic', fn ($q) => $q->whereIn('subject_id', $subjectIds));
        } else {
            $query->whereHas('topic', fn ($q) => $q->where('subject_id', $exam->subject_id));
        }

        if ($exam->school_class_id) {
            $query->where('school_class_id', $exam->school_class_id);
        }

        return $query->with(['topic.subject'])->get();
    }

    /**
     * Internal helper to pull questions following the biennial rotation policy.
     */
    protected function pullQuestionsForCriteria(
        string $subjectId,
        ?string $topicId,
        ?string $schoolClassId,
        int $count,
        CarbonInterface $twoYearsAgo
    ): array {
        $query = Question::whereHas('topic', fn ($q) => $q->where('subject_id', $subjectId));

        if ($topicId) {
            $query->where('topic_id', $topicId);
        }

        if ($schoolClassId) {
            $query->where('school_class_id', $schoolClassId);
        }

        $primaryPool = (clone $query)
            ->where(function ($q) use ($twoYearsAgo) {
                $q->whereNull('last_used_at')->orWhere('last_used_at', '<', $twoYearsAgo);
            })
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->toArray();

        $selectedIds = $primaryPool;

        if (count($selectedIds) < $count) {
            $remainingNeeded = $count - count($selectedIds);
            $secondaryIds = (clone $query)
                ->whereNotIn('id', $selectedIds)
                ->orderBy('last_used_at', 'asc')
                ->limit($remainingNeeded)
                ->pluck('id')
                ->toArray();

            $selectedIds = array_merge($selectedIds, $secondaryIds);
        }

        return $selectedIds;
    }
}
