<?php

namespace App\Services;

use App\Enums\AttemptStatus;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Repositories\Contracts\AttemptRepositoryInterface;
use App\Repositories\Contracts\ExamRepositoryInterface;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function __construct(
        protected ExamRepositoryInterface $examRepo,
        protected QuestionRepositoryInterface $questionRepo,
        protected AttemptRepositoryInterface $attemptRepo
    ) {}

    /**
     * Create a new exam.
     */
    public function createExam(array $data, string $creatorId): Exam
    {
        return DB::transaction(function () use ($data, $creatorId) {
            $compositions = $data['compositions'] ?? [];
            unset($data['compositions']);

            $exam = Exam::create([
                ...$data,
                'created_by' => $creatorId,
                'status' => ExamStatus::DRAFT,
            ]);

            foreach ($compositions as $compDTO) {
                $exam->compositions()->create($compDTO->toArray());
            }

            return $exam;
        });
    }

    /**
     * Update questions for a specific exam.
     */
    public function updateExamQuestions(Exam $exam, array $questionIds): void
    {
        $exam->questions()->sync($questionIds);
    }

    /**
     * Automatically select questions for an exam using the biennial rotation policy.
     */
    public function autoSelectQuestions(Exam $exam, ?int $totalCount = null): int
    {
        return DB::transaction(function () use ($exam, $totalCount) {
            $twoYearsAgo = now()->subYears(2);
            $selectedQuestionIds = [];

            // Case 1: Multi-subject Blueprint (Compositions)
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
            }
            // Case 2: Standard Single-subject Exam
            elseif ($totalCount && $exam->subject_id) {
                $selectedQuestionIds = $this->pullQuestionsForCriteria(
                    subjectId: $exam->subject_id,
                    topicId: null,
                    schoolClassId: $exam->school_class_id,
                    count: $totalCount,
                    twoYearsAgo: $twoYearsAgo
                );
            }

            // Sync all selected questions
            if (! empty($selectedQuestionIds)) {
                $exam->questions()->sync($selectedQuestionIds);
                \App\Models\Question::whereIn('id', $selectedQuestionIds)->update(['last_used_at' => now()]);
            }

            return count($selectedQuestionIds);
        });
    }

    /**
     * Internal helper to pull questions following the biennial rotation policy.
     */
    protected function pullQuestionsForCriteria(
        string $subjectId,
        ?string $topicId,
        ?string $schoolClassId,
        int $count,
        \Carbon\CarbonInterface $twoYearsAgo
    ): array {
        $query = \App\Models\Question::whereHas('topic', fn ($q) => $q->where('subject_id', $subjectId));

        if ($topicId) {
            $query->where('topic_id', $topicId);
        }

        if ($schoolClassId) {
            $query->where('school_class_id', $schoolClassId);
        }

        // 1. Primary Pool: Compliant questions (not used in last 2 years)
        $primaryPool = (clone $query)
            ->where(function ($q) use ($twoYearsAgo) {
                $q->whereNull('last_used_at')->orWhere('last_used_at', '<', $twoYearsAgo);
            })
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->toArray();

        $selectedIds = $primaryPool;

        // 2. Secondary Pool: Fallback to Least Recently Used (LRU)
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

    /**
     * Get available questions for an exam context.
     */
    public function getAvailableQuestions(Exam $exam): Collection
    {
        $query = \App\Models\Question::query();

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
     * Start a new exam attempt for a student.
     */
    public function startExam(User $user, Exam $exam): ExamAttempt
    {
        return DB::transaction(function () use ($user, $exam) {
            // Check eligibility (repository method would be ideal here)
            $existingAttempt = ExamAttempt::where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->first();

            if ($existingAttempt) {
                if ($existingAttempt->status === AttemptStatus::SUBMITTED) {
                    throw new \Exception('You have already completed this examination. Only one attempt is permitted.');
                }

                return $existingAttempt;
            }

            // Create Attempt
            $attempt = $this->attemptRepo->create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'status' => AttemptStatus::ONGOING,
                'started_at' => now(),
            ]);

            // Locked-in Shuffling
            $questions = $exam->questions()->with('options')->get();
            $seed = $attempt->id;

            $shuffledQuestionIds = $questions->values()
                ->sortBy(fn ($q) => hash('sha256', $q->id.$seed))
                ->pluck('id')
                ->toArray();

            $optionMap = [];
            foreach ($questions as $question) {
                $optionMap[$question->id] = $question->options->values()
                    ->sortBy(fn ($o) => hash('sha256', $o->id.$seed))
                    ->pluck('id')
                    ->toArray();
            }

            $this->attemptRepo->updateMetadata($attempt->id, [
                'question_order' => $shuffledQuestionIds,
                'option_orders' => $optionMap,
            ]);

            return $attempt->fresh();
        });
    }

    /**
     * Get the questions for an attempt in their locked-in order.
     */
    public function getAttemptQuestions(ExamAttempt $attempt): Collection
    {
        $metadata = $attempt->metadata;
        $questionOrder = $metadata['question_order'] ?? [];
        $optionOrders = $metadata['option_orders'] ?? [];

        $questions = \App\Models\Question::query()
            ->whereIn('id', $questionOrder)
            ->with('options')
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $questionOrder))
            ->values();

        $questions->each(function ($question) use ($optionOrders) {
            $order = $optionOrders[$question->id] ?? [];
            if (! empty($order)) {
                $question->setRelation(
                    'options',
                    $question->options->sortBy(fn ($o) => array_search($o->id, $order))->values()
                );
            }
        });

        return $questions;
    }

    /**
     * Submit an exam attempt and calculate the score.
     */
    public function submitAttempt(ExamAttempt $attempt, array $answers, array $additionalMetadata = [], array $violations = []): void
    {
        DB::transaction(function () use ($attempt, $answers, $additionalMetadata, $violations) {
            if ($attempt->status !== AttemptStatus::ONGOING) {
                return;
            }

            $totalScore = 0;
            $questions = $this->getAttemptQuestions($attempt)->load('topic');
            $exam = $attempt->exam->load('compositions');

            $marksMap = [];
            if ($exam->compositions->isNotEmpty()) {
                foreach ($exam->compositions as $comp) {
                    $key = $comp->topic_id ? "t_{$comp->topic_id}" : "s_{$comp->subject_id}";
                    $marksMap[$key] = (float) $comp->marks_per_question;
                }
            }

            foreach ($questions as $question) {
                $selectedOptionId = $answers[$question->id] ?? null;
                $isCorrect = false;

                if ($selectedOptionId) {
                    $option = $question->options->firstWhere('id', $selectedOptionId);
                    $isCorrect = $option ? $option->is_correct : false;
                }

                $marks = 1.00;
                if (! empty($marksMap)) {
                    $marks = $marksMap["t_{$question->topic_id}"]
                          ?? $marksMap["s_{$question->topic->subject_id}"]
                          ?? 1.00;
                }

                $earned = $isCorrect ? $marks : 0.00;
                $totalScore += $earned;

                \App\Models\ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_options' => $selectedOptionId ? [$selectedOptionId] : [],
                    'is_correct' => $isCorrect,
                    'score' => $earned,
                ]);
            }

            $finalMetadata = array_merge($attempt->metadata ?? [], $additionalMetadata);
            $this->attemptRepo->submit($attempt->id, $totalScore, $finalMetadata['termination_reason'] ?? null);

            // Finalize metadata update (if not handled by submit)
            $attempt->update(['metadata' => $finalMetadata, 'violations' => $violations]);
        });
    }
}
