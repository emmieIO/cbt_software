<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Services\ExamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

class ExamReadService
{
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
    public function getExamDetails(Exam $exam): Exam
    {
        return $exam->load([
            'subject',
            'schoolClass',
            'academicSession',
            'questions.topic.subject',
            'compositions.subject',
            'compositions.topic',
        ]);
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
            'exam' => $exam,
            'availableQuestions' => $availableQuestions,
            'selectedQuestionIds' => $exam->questions->pluck('id'),
        ];
    }
}
