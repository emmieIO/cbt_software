<?php

namespace App\Services;

use App\DTOs\ExamDTO;
use App\DTOs\ExamVersionDTO;
use App\Models\Exam;
use App\Models\ExamVersion;
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
            return Exam::create([
                ...$data,
                'created_by' => $creatorId,
                'status' => 'draft',
            ]);
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
     *
     * Policy:
     * 1. Prioritize questions never used or used > 2 years ago.
     * 2. Fallback to Least Recently Used (LRU) if quota not met.
     */
    public function autoSelectQuestions(Exam $exam, int $count): int
    {
        return DB::transaction(function () use ($exam, $count) {
            $twoYearsAgo = now()->subYears(2);

            // 1. Primary Pool: Compliant questions (Never used OR used > 2 years ago)
            $primaryPool = Question::whereHas('topic', fn ($q) => $q->where('subject_id', $exam->subject_id))
                ->where('school_class_id', $exam->school_class_id)
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

                $secondaryPool = Question::whereHas('topic', fn ($q) => $q->where('subject_id', $exam->subject_id))
                    ->where('school_class_id', $exam->school_class_id)
                    ->whereNotIn('id', $selectedQuestions->pluck('id'))
                    ->orderBy('last_used_at', 'asc') // Oldest usage first
                    ->limit($remainingNeeded)
                    ->get();

                $selectedQuestions = $selectedQuestions->concat($secondaryPool);
            }

            // Sync selected questions
            $exam->questions()->sync($selectedQuestions->pluck('id'));

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
        return Question::whereHas('topic', fn ($q) => $q->where('subject_id', $exam->subject_id))
            ->where('school_class_id', $exam->school_class_id)
            ->with('topic')
            ->get();
    }

    /**
     * Start a new exam attempt for a student.
     */
    public function startExam(\App\Models\User $user, \App\Models\Exam $exam): \App\Models\ExamAttempt
    {
        return DB::transaction(function () use ($user, $exam) {
            // 1. Eligibility Check: Ensure no active attempt already exists
            $existingAttempt = \App\Models\ExamAttempt::where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->where('status', \App\Enums\AttemptStatus::ONGOING)
                ->first();

            if ($existingAttempt) {
                return $existingAttempt;
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
            $seed = crc32($attempt->id);

            // Shuffle questions and capture IDs
            $shuffledQuestionIds = $questions->shuffle($seed)->pluck('id')->toArray();

            // Shuffle options for every question and capture their order
            $optionMap = [];
            foreach ($questions as $question) {
                $questionSeed = $seed + crc32($question->id);
                $optionMap[$question->id] = $question->options
                    ->shuffle($questionSeed)
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
        $questions = \App\Models\Question::whereIn('id', $questionOrder)
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
    public function submitAttempt(\App\Models\ExamAttempt $attempt, array $answers): void
    {
        DB::transaction(function () use ($attempt, $answers) {
            if ($attempt->status !== \App\Enums\AttemptStatus::ONGOING) {
                return;
            }

            $score = 0;
            $questions = $this->getAttemptQuestions($attempt);

            foreach ($questions as $question) {
                $selectedOptionId = $answers[$question->id] ?? null;
                $isCorrect = false;

                if ($selectedOptionId) {
                    $option = $question->options->firstWhere('id', $selectedOptionId);
                    $isCorrect = $option ? $option->is_correct : false;
                }

                if ($isCorrect) {
                    $score++;
                }

                // Record the answer
                \App\Models\ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'option_id' => $selectedOptionId,
                    'is_correct' => $isCorrect,
                    'marks_earned' => $isCorrect ? 1.00 : 0.00, // Assuming 1 mark per question for now
                ]);
            }

            $attempt->update([
                'status' => \App\Enums\AttemptStatus::SUBMITTED,
                'submitted_at' => now(),
                'score' => $score,
            ]);
        });
    }
}
