<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;

class ExamPrintService
{
    /**
     * Get data for student-specific result print view.
     *
     * @return array{exam: Exam, student: User, attempt: ExamAttempt, totalQuestions: int}
     */
    public function getStudentResultPrintData(Exam $exam, User $student): array
    {
        $exam->load(['subject', 'schoolClass', 'academicSession']);

        $attempt = $exam->attempts()
            ->where('user_id', $student->id)
            ->with(['user.schoolClass'])
            ->firstOrFail();

        return [
            'exam' => $exam,
            'student' => $student,
            'attempt' => $attempt,
            'totalQuestions' => $exam->questions()->count(),
        ];
    }

    /**
     * Load exam relations needed for hard-copy paper rendering.
     */
    public function loadHardCopyData(Exam $exam): Exam
    {
        return $exam->load([
            'school',
            'subject',
            'schoolClass',
            'academicSession',
            'questions' => function ($query) {
                $query->with(['options', 'topic.subject'])->orderByPivot('order', 'asc');
            },
        ]);
    }

    /**
     * Load exam relations needed for answer-sheet rendering.
     */
    public function loadAnswerSheetData(Exam $exam): Exam
    {
        return $exam->load([
            'school',
            'subject',
            'schoolClass',
            'academicSession',
            'questions' => function ($query) {
                $query->orderByPivot('order', 'asc');
            },
        ]);
    }

    /**
     * Get data for exam-wide results print view.
     *
     * @return array{exam: Exam, totalQuestions: int}
     */
    public function getResultsPrintData(Exam $exam): array
    {
        $exam->load([
            'school',
            'subject',
            'schoolClass',
            'academicSession',
            'attempts' => function ($query) {
                $query->with(['user.schoolClass'])->latest('score');
            },
        ]);

        return [
            'exam' => $exam,
            'totalQuestions' => $exam->questions()->count(),
        ];
    }
}
