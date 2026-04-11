<?php

namespace App\Services\Exam;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\User;
use App\Services\QuestionService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ExamPayloadService
{
    public function __construct(protected QuestionService $questionService) {}

    private function canAuthorAcrossLevels(User $user): bool
    {
        return $user->can('access:cross-level-authoring')
            || $user->can('bank:create_cross_level')
            || $user->can('exam:create_cross_level');
    }

    /**
     * Build common create/edit form payload for exam screens.
     */
    public function getFormPayload(User $user): array
    {
        $context = $this->questionService->getAuthorizedContext(
            $user,
            false,
            $this->canAuthorAcrossLevels($user)
        );

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
        $loadedExam = $exam->load(['subject', 'schoolClass', 'compositions.subject', 'compositions.topic']);
        $examPayload = $loadedExam->toArray();
        $examPayload['start_time'] = $this->formatDateTime($loadedExam->start_time);
        $examPayload['end_time'] = $this->formatDateTime($loadedExam->end_time);

        return [
            'exam' => $examPayload,
            ...$this->getFormPayload($user),
        ];
    }

    protected function formatDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
