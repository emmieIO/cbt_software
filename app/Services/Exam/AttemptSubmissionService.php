<?php

namespace App\Services\Exam;

use App\Enums\AttemptStatus;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Repositories\Contracts\AttemptRepositoryInterface;
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

            $totalScore = 0;
            $questions = $this->attemptLifecycleService->getAttemptQuestions($attempt)->load('topic');
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

                ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_options' => $selectedOptionId ? [$selectedOptionId] : [],
                    'is_correct' => $isCorrect,
                    'score' => $earned,
                ]);
            }

            $finalMetadata = array_merge($attempt->metadata ?? [], $additionalMetadata);
            $this->attemptRepo->submit($attempt->id, $totalScore, $finalMetadata['termination_reason'] ?? null);

            $attempt->update(['metadata' => $finalMetadata, 'violations' => $violations]);
        });
    }
}
