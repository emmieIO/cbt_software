<?php

namespace App\Services\Exam;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\User;
use App\Services\QuestionService;

class ExamPayloadService
{
    public function __construct(protected QuestionService $questionService) {}

    /**
     * Build common create/edit form payload for exam screens.
     */
    public function getFormPayload(User $user): array
    {
        $context = $this->questionService->getAuthorizedContext($user, false);

        return [
            'sessions' => AcademicSession::query()->current()->get(),
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
        ];
    }

    /**
     * Build edit payload with loaded exam relationships.
     */
    public function getEditPayload(User $user, Exam $exam): array
    {
        return [
            'exam' => $exam->load(['subject', 'schoolClass', 'compositions.subject', 'compositions.topic']),
            ...$this->getFormPayload($user),
        ];
    }
}
