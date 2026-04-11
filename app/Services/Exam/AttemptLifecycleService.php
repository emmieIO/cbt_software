<?php

namespace App\Services\Exam;

use App\Enums\AttemptStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;
use App\Repositories\Contracts\AttemptRepositoryInterface;
use App\Services\Student\StudentPortalService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AttemptLifecycleService
{
    public function __construct(
        protected AttemptRepositoryInterface $attemptRepo,
        protected StudentPortalService $studentPortalService
    ) {}

    /**
     * Start a new exam attempt for a student.
     */
    public function startExam(User $user, Exam $exam): ExamAttempt
    {
        return DB::transaction(function () use ($user, $exam) {
            $existingAttempt = ExamAttempt::where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->first();

            if ($existingAttempt) {
                if ($existingAttempt->status === AttemptStatus::SUBMITTED) {
                    throw new Exception('You have already completed this examination. Only one attempt is permitted.');
                }

                if ($this->studentPortalService->examWindowHasClosed($exam)) {
                    throw new Exception('This examination window has closed.');
                }

                return $existingAttempt;
            }

            $this->ensureExamCanBeStarted($user, $exam);

            $attempt = $this->attemptRepo->create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'status' => AttemptStatus::ONGOING,
                'started_at' => now(),
            ]);

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

    protected function ensureExamCanBeStarted(User $user, Exam $exam): void
    {
        if (! $this->studentPortalService->userCanAccessExam($user, $exam)) {
            throw new Exception('You are not allowed to start this examination.');
        }

        if (! $exam->questions()->exists()) {
            throw new Exception('This examination is not ready yet because no questions have been assigned.');
        }
    }

    /**
     * Get the questions for an attempt in their locked-in order.
     */
    public function getAttemptQuestions(ExamAttempt $attempt): Collection
    {
        $metadata = $attempt->metadata;
        $questionOrder = $metadata['question_order'] ?? [];
        $optionOrders = $metadata['option_orders'] ?? [];

        $questions = Question::query()
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
}
