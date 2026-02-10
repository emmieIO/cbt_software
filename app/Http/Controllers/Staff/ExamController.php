<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\DTOs\ExamDTO;
use App\DTOs\ExamVersionDTO;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamVersion;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(
        protected ExamService $examService
    ) {}

    /**
     * List all exams the teacher is authorized to see.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Exam::with(['subject', 'schoolClass', 'academicSession'])
            ->withCount('questions');

        // Scoping: Staff only see their own exams or exams for their assigned loads
        if (! $user->hasRole('admin')) {
            $assignedClassIds = $user->currentAssignments()->pluck('school_class_id')->unique();
            $assignedSubjectIds = $user->currentAssignments()->pluck('subject_id')->unique();

            $query->whereIn('school_class_id', $assignedClassIds)
                  ->whereIn('subject_id', $assignedSubjectIds);
        }

        return Inertia::render('Staff/Exams/Index', [
            'exams' => $query->latest()->paginate(10),
            'filters' => $request->only(['status', 'type']),
        ]);
    }

    /**
     * Show create form with assigned classes/subjects.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        
        // Get only assigned loads for this teacher
        $assignments = $user->currentAssignments()
            ->with(['schoolClass', 'subject'])
            ->get();

        return Inertia::render('Staff/Exams/Create', [
            'assignments' => $assignments,
            'sessions' => AcademicSession::current()->get(),
        ]);
    }

    /**
     * Store a new exam.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string'], // ExamType enum
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
        ]);

        $currentSession = AcademicSession::current()->firstOrFail();
        
        $dto = ExamDTO::fromRequest($request, $currentSession->id);
        $exam = $this->examService->createExam($dto, $request->user()->id);

        return redirect()->route('staff.exams.show', $exam->id)
            ->with('success', 'Exam configuration saved. Now allocate your questions.');
    }

    /**
     * Show exam details.
     */
    public function show(Exam $exam): Response
    {
        $exam->load(['subject', 'schoolClass', 'questions']);

        return Inertia::render('Staff/Exams/Show', [
            'exam' => $exam,
        ]);
    }

    /**
     * Show the question management page for an exam.
     */
    public function manageQuestions(Exam $exam): Response
    {
        $exam->load(['subject', 'schoolClass', 'questions']);

        $availableQuestions = $this->examService->getAvailableQuestions($exam);

        return Inertia::render('Staff/Exams/Questions', [
            'exam' => $exam,
            'availableQuestions' => $availableQuestions,
            'selectedQuestionIds' => $exam->questions->pluck('id'),
        ]);
    }

    /**
     * Update questions for an exam.
     */
    public function updateQuestions(Request $request, Exam $exam): RedirectResponse
    {
        $request->validate([
            'question_ids' => ['required', 'array'],
            'question_ids.*' => ['exists:questions,id'],
        ]);

        $this->examService->updateExamQuestions($exam, $request->question_ids);

        return redirect()->route('staff.exams.show', $exam->id)
            ->with('success', 'Questions allocated to the exam successfully.');
    }

    /**
     * Auto-select questions using AI/Random logic.
     */
    public function aiSelectQuestions(Request $request, Exam $exam): RedirectResponse
    {
        $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $count = $this->examService->autoSelectQuestions($exam, $request->count);

        return back()->with('success', 'AI has balanced and selected ' . $count . ' questions following the biennial rotation policy.');
    }
}