<?php

namespace App\Services\Exam;

use App\Enums\AttemptStatus;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Repositories\Contracts\AttemptRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AttemptSubmissionService
{
    public function __construct(
        protected AttemptRepositoryInterface $attemptRepo,
        protected AttemptLifecycleService $attemptLifecycleService
    ) {}

    /**
     * Submit an exam attempt and calculate the score.
     */
    public function submitAttempt(ExamAttempt $attempt, array $answers, array $additionalMetadata = [], array $violations = []): void
    {
        DB::transaction(function () use ($attempt, $answers, $additionalMetadata, $violations) {
            if ($attempt->status !== AttemptStatus::ONGOING) {
                return;
            }

            $timedOut = $this->hasTimedOut($attempt);
            $totalScore = 0;
            $questions = $this->attemptLifecycleService->getAttemptQuestions($attempt);
            $effectiveAnswers = $this->resolveAnswersForSubmission($attempt, $answers, $timedOut);

            foreach ($questions as $question) {
                $selectedOptionId = $effectiveAnswers[$question->id] ?? null;
                $isCorrect = false;

                if ($selectedOptionId) {
                    $option = $question->options->firstWhere('id', $selectedOptionId);
                    $isCorrect = $option ? $option->is_correct : false;
                }

                // Official scoring policy: one point per correct response.
                $earned = $isCorrect ? 1.00 : 0.00;
                $totalScore += $earned;

                ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_options' => $selectedOptionId ? [$selectedOptionId] : [],
                    'is_correct' => $isCorrect,
                    'score' => $earned,
                ]);
            }

            $finalMetadata = array_merge($attempt->metadata ?? [], $additionalMetadata);
            if ($timedOut) {
                $finalMetadata['termination_reason'] = 'timeout';
            }

            $this->attemptRepo->submit($attempt->id, $totalScore, $finalMetadata['termination_reason'] ?? null);

            $attempt->update(['metadata' => $finalMetadata, 'violations' => $violations]);
        });
    }

    public function hasTimedOut(ExamAttempt $attempt): bool
    {
        $deadline = $this->submissionDeadline($attempt);

        return $deadline instanceof CarbonInterface && now()->greaterThan($deadline);
    }

    /**
     * @param  array<string, string>  $answers
     * @return array<string, string>
     */
    protected function resolveAnswersForSubmission(ExamAttempt $attempt, array $answers, bool $timedOut): array
    {
        $savedAnswers = $attempt->metadata['saved_answers'] ?? [];

        if ($timedOut) {
            return is_array($savedAnswers) ? $savedAnswers : [];
        }

        return array_merge(
            is_array($savedAnswers) ? $savedAnswers : [],
            $answers
        );
    }

    protected function submissionDeadline(ExamAttempt $attempt): ?CarbonInterface
    {
        /** @var \App\Models\Exam|null $exam */
        $exam = $attempt->exam()->first();

        if (! $attempt->started_at || ! $exam) {
            return null;
        }

        return Carbon::parse($attempt->started_at)->addMinutes((int) $exam->duration);
    }
}
