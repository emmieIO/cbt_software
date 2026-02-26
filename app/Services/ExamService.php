<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExamService
{
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
                'status' => 'draft',
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
     * Supports both single-subject and multi-subject (composition) exams.
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
                        prospectiveClassId: $exam->prospective_class_id,
                        schoolClassId: $composition->source_class_id ?? $exam->school_class_id,
                        count: $composition->question_count,
                        twoYearsAgo: $twoYearsAgo,
                        isEntrance: $exam->type === \App\Enums\ExamType::ENTRANCE
                    );
                    $selectedQuestionIds = array_merge($selectedQuestionIds, $sectionIds);
                }
            } 
            // Case 2: Standard Single-subject Exam
            else if ($totalCount && $exam->subject_id) {
                $selectedQuestionIds = $this->pullQuestionsForCriteria(
                    subjectId: $exam->subject_id,
                    topicId: null,
                    prospectiveClassId: $exam->prospective_class_id,
                    schoolClassId: $exam->school_class_id,
                    count: $totalCount,
                    twoYearsAgo: $twoYearsAgo,
                    isEntrance: $exam->type === \App\Enums\ExamType::ENTRANCE
                );
            }

            // Sync all selected questions
            if (!empty($selectedQuestionIds)) {
                $exam->questions()->sync($selectedQuestionIds);
                Question::whereIn('id', $selectedQuestionIds)->update(['last_used_at' => now()]);
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
        ?string $prospectiveClassId, 
        ?string $schoolClassId, 
        int $count, 
        \Carbon\CarbonInterface $twoYearsAgo,
        bool $isEntrance = false
    ): array {
        $query = Question::whereHas('topic', fn ($q) => $q->where('subject_id', $subjectId));

        if ($topicId) {
            $query->where('topic_id', $topicId);
        }

        // For entrance exams, we are more flexible with class/batch filtering
        // unless specific batch questions are required.
        if (!$isEntrance) {
            if ($prospectiveClassId) {
                $query->where('prospective_class_id', $prospectiveClassId);
            } else {
                $query->where('school_class_id', $schoolClassId);
            }
        } else {
            // Entrance Exams: Prefer batch-specific, fallback to source class (level) if provided
            $hasBatchQuestions = (clone $query)->where('prospective_class_id', $prospectiveClassId)->exists();
            if ($hasBatchQuestions) {
                $query->where('prospective_class_id', $prospectiveClassId);
            } elseif ($schoolClassId) {
                $query->where('school_class_id', $schoolClassId);
            }
        }

        // 1. Primary Pool: Compliant questions
        $primaryPool = (clone $query)
            ->where(function ($q) use ($twoYearsAgo) {
                $q->whereNull('last_used_at')->orWhere('last_used_at', '<', $twoYearsAgo);
            })
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->toArray();

        $selectedIds = $primaryPool;

        // 2. Secondary Pool: Fallback to LRU
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
        $query = Question::query();

        // Case 1: Multi-subject Blueprint
        if ($exam->compositions()->exists()) {
            $subjectIds = $exam->compositions->pluck('subject_id')->unique();
            $query->whereHas('topic', fn ($q) => $q->whereIn('subject_id', $subjectIds));
            
            // Note: For multi-subject manual select, we currently pull ALL questions from these subjects.
            // If we wanted to be stricter, we'd need to join or union queries per composition.
            // For now, we'll let the user see the whole pool of those subjects.
        } 
        // Case 2: Standard Single-subject Exam
        else {
            $query->whereHas('topic', fn ($q) => $q->where('subject_id', $exam->subject_id));
        }

        // Relaxed filtering for entrance exams
        if ($exam->type !== \App\Enums\ExamType::ENTRANCE) {
            if ($exam->prospective_class_id) {
                $query->where('prospective_class_id', $exam->prospective_class_id);
            } else {
                $query->where('school_class_id', $exam->school_class_id);
            }
        } else {
            // For Entrance: If batch-specific questions exist, show them, 
            // otherwise show everything in those subjects
            $hasBatchQuestions = (clone $query)->where('prospective_class_id', $exam->prospective_class_id)->exists();
            if ($hasBatchQuestions) {
                $query->where('prospective_class_id', $exam->prospective_class_id);
            }
        }

        return $query->with(['topic.subject'])->get();
    }

    /**
     * Start a new exam attempt for a student.
     */
    public function startExam(\App\Models\User $user, Exam $exam): \App\Models\ExamAttempt
    {
        return DB::transaction(function () use ($user, $exam) {
            // 1. Eligibility Check: Ensure no attempt (ongoing or submitted) already exists
            $existingAttempt = \App\Models\ExamAttempt::where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->first();

            if ($existingAttempt) {
                if ($existingAttempt->status === \App\Enums\AttemptStatus::SUBMITTED) {
                    throw new \Exception('You have already completed this examination. Only one attempt is permitted.');
                }

                return $existingAttempt; // Return ongoing attempt
            }

            // 2. Create the Attempt
            $attempt = \App\Models\ExamAttempt::create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'status' => \App\Enums\AttemptStatus::ONGOING,
                'started_at' => now(),
            ]);

            // 3. Locked-in Shuffling: Save the unique sequence for this student
            $questions = $exam->questions()->with('options')->get();
            $seed = $attempt->id;

            // Stable shuffle questions using a hash of (ID + attempt seed)
            $shuffledQuestionIds = $questions->values()
                ->sortBy(fn ($q) => hash('sha256', $q->id.$seed))
                ->pluck('id')
                ->toArray();

            // Stable shuffle options for every question
            $optionMap = [];
            foreach ($questions as $question) {
                $optionMap[$question->id] = $question->options->values()
                    ->sortBy(fn ($o) => hash('sha256', $o->id.$seed))
                    ->pluck('id')
                    ->toArray();
            }

            // Save sequence to metadata for persistence and audit
            $attempt->update([
                'status' => \App\Enums\AttemptStatus::ONGOING,
                'metadata' => [
                    'question_order' => $shuffledQuestionIds,
                    'option_orders' => $optionMap,
                ],
            ]);

            return $attempt;
        });
    }

    /**
     * Get the questions for an attempt in their locked-in order.
     */
    public function getAttemptQuestions(\App\Models\ExamAttempt $attempt): Collection
    {
        $metadata = $attempt->metadata;
        $questionOrder = $metadata['question_order'] ?? [];
        $optionOrders = $metadata['option_orders'] ?? [];

        // Fetch all questions in the attempt pool
        $questions = Question::query()
            ->whereIn('id', $questionOrder)
            ->with('options')
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $questionOrder))
            ->values();

        // Sort options for each question based on the locked-in order
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
    public function submitAttempt(\App\Models\ExamAttempt $attempt, array $answers, array $additionalMetadata = []): void
    {
        DB::transaction(function () use ($attempt, $answers, $additionalMetadata) {
            if ($attempt->status !== \App\Enums\AttemptStatus::ONGOING) {
                return;
            }

            $totalScore = 0;
            $questions = $this->getAttemptQuestions($attempt)->load('topic');
            $exam = $attempt->exam->load('compositions');

            // Cache marks per subject/topic for performance
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

                // Determine marks for this question
                $marks = 1.00; // Default
                if (!empty($marksMap)) {
                    // Try topic-specific match first, then subject match
                    $marks = $marksMap["t_{$question->topic_id}"] 
                          ?? $marksMap["s_{$question->topic->subject_id}"] 
                          ?? 1.00;
                }

                $earned = $isCorrect ? $marks : 0.00;
                $totalScore += $earned;

                // Record the answer
                \App\Models\ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'option_id' => $selectedOptionId,
                    'is_correct' => $isCorrect,
                    'marks_earned' => $earned,
                ]);
            }

            $finalMetadata = array_merge($attempt->metadata ?? [], $additionalMetadata);

            $attempt->update([
                'status' => \App\Enums\AttemptStatus::SUBMITTED,
                'submitted_at' => now(),
                'score' => $totalScore,
                'metadata' => $finalMetadata,
            ]);
        });
    }
}
