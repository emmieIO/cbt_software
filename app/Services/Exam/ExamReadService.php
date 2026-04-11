<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Services\ExamService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Carbon;

class ExamReadService
{
    /**
     * Normalize exam timestamps for frontend forms and tables so the browser
     * does not apply an extra timezone shift on serialized model dates.
     *
     * @return array<string, mixed>
     */
    public function normalizeExamForFrontend(Exam $exam): array
    {
        $payload = $exam->toArray();
        $payload['start_time'] = $this->formatDateTime($exam->start_time);
        $payload['end_time'] = $this->formatDateTime($exam->end_time);

        return $payload;
    }

    /**
     * Build the scoped query for exam listing.
     */
    public function queryVisibleExams(User $user, ?string $schoolId = null): Builder
    {
        $query = Exam::with(['subject', 'schoolClass', 'academicSession'])
            ->withCount('questions');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if (! $user->can('sys:manage_settings')) {
            $query->where('school_id', $user->school_id);
        }

        return $query;
    }

    /**
     * Load full exam details for the staff show page.
     */
    public function getExamDetails(Exam $exam): array
    {
        $exam->load([
            'subject',
            'schoolClass',
            'academicSession',
            'questions.topic.subject',
            'compositions.subject',
            'compositions.topic',
        ]);

        return $this->normalizeExamForFrontend($exam);
    }

    /**
     * Assemble data for question management screen.
     *
     * @return array{exam: Exam, availableQuestions: EloquentCollection<int, Question>, selectedQuestionIds: SupportCollection<int, string>}
     */
    public function getExamQuestionManagementData(Exam $exam, ExamService $examService): array
    {
        $exam->load(['subject', 'schoolClass', 'questions', 'compositions.subject', 'compositions.topic']);

        $availableQuestions = $examService->getAvailableQuestions($exam);

        return [
            'exam' => $this->normalizeExamForFrontend($exam),
            'availableQuestions' => $availableQuestions,
            'selectedQuestionIds' => $exam->questions->pluck('id'),
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
