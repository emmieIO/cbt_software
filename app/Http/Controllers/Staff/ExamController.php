<?php

namespace App\Http\Controllers\Staff;

use App\DTOs\ExamDTO;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamController extends Controller
{
    public function __construct(
        protected ExamService $examService
    ) {}

    /**
     * List all exams the teacher is authorized to see.
     */
    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();
        $query = Exam::with(['subject', 'schoolClass', 'academicSession'])
            ->withCount('questions');

        if ($request->school_id) {
            $query->where('school_id', $request->school_id);
        }

        // Scoping Logic
        if (! $user->can('sys:manage_settings')) {
            // Staff/Examiners are strictly scoped to their assigned school branch
            $query->where('school_id', $user->school_id);

            // If the user is a regular teacher (doesn't have broad management rights),
            // we could further restrict to their assignments.
            // However, based on the requirement for examiners to see exams created by others (like Super Admin),
            // we allow school-wide visibility for anyone with 'exam:view'.
        }

        return Inertia::render('Staff/Exams/Index', [
            'exams' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['status', 'type', 'school_id']),
        ]);
    }

    /**
     * Show create form with assigned classes/subjects.
     */
    public function create(Request $request): \Inertia\Response
    {
        $user = $request->user();

        // Get authorized context (scoped to their school tier)
        $context = app(\App\Services\QuestionService::class)->getAuthorizedContext($user, false);

        return Inertia::render('Staff/Exams/Create', [
            'sessions' => AcademicSession::query()->current()->get(),
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
        ]);
    }

    /**
     * Store a new exam.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'school_id' => ['required', 'exists:schools,id'],
            'subject_id' => [
                'required_without:compositions',
                'nullable',
                'exists:subjects,id',
            ],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string'], // ExamType enum
            'start_time' => ['nullable', 'date', 'after_or_equal:now'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'compositions' => ['nullable', 'array'],
            'compositions.*.subject_id' => ['required', 'exists:subjects,id'],
            'compositions.*.topic_id' => ['nullable', 'exists:topics,id'],
            'compositions.*.question_count' => ['required', 'integer', 'min:1'],
            'compositions.*.marks_per_question' => ['required', 'numeric', 'min:0.1'],
        ]);

        $currentSession = AcademicSession::current()->first();

        if (! $currentSession) {
            return back()->with('error', 'No current academic session found. Please contact an administrator to set an active session.');
        }

        $dto = ExamDTO::fromRequest($request, $currentSession->id);

        // Sync branch slug
        $school = \App\Models\School::find($request->school_id);
        $data = $dto->toArray();
        $data['branch'] = $school->slug;

        $exam = $this->examService->createExam($data, $request->user()->id);

        return redirect()->route('staff.exams.show', $exam->id)
            ->with('success', 'Exam configuration saved. Now allocate your questions.');
    }

    /**
     * Show exam details.
     */
    public function show(Exam $exam): \Inertia\Response
    {
        $exam->load([
            'subject',
            'schoolClass',
            'academicSession',
            'questions.topic.subject',
            'compositions.subject',
            'compositions.topic',
        ]);

        return Inertia::render('Staff/Exams/Show', [
            'exam' => $exam,
        ]);
    }

    /**
     * Show the question management page for an exam.
     */
    public function manageQuestions(Exam $exam): \Inertia\Response
    {
        $exam->load(['subject', 'schoolClass', 'questions', 'compositions.subject', 'compositions.topic']);

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
    public function updateQuestions(Request $request, Exam $exam): \Illuminate\Http\RedirectResponse
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
    public function aiSelectQuestions(Request $request, Exam $exam): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $count = $this->examService->autoSelectQuestions($exam, $request->count);

        return back()->with('success', 'AI has balanced and selected '.$count.' questions following the biennial rotation policy.');
    }

    /**
     * Show the edit form.
     */
    public function edit(Request $request, Exam $exam): \Inertia\Response
    {
        $user = $request->user();

        // Get authorized context
        $context = app(\App\Services\QuestionService::class)->getAuthorizedContext($user, false);

        return Inertia::render('Staff/Exams/Edit', [
            'exam' => $exam->load(['subject', 'schoolClass', 'compositions.subject', 'compositions.topic']),
            'sessions' => AcademicSession::query()->current()->get(),
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
        ]);
    }

    /**
     * Update the exam.
     */
    public function update(Request $request, Exam $exam): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'school_id' => ['required', 'exists:schools,id'],
            'subject_id' => [
                'required_without:compositions',
                'nullable',
                'exists:subjects,id',
            ],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string'], // ExamType enum
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'status' => ['required', 'string'], // ExamStatus enum
            'compositions' => ['nullable', 'array'],
            'compositions.*.subject_id' => ['required', 'exists:subjects,id'],
            'compositions.*.topic_id' => ['nullable', 'exists:topics,id'],
            'compositions.*.question_count' => ['required', 'integer', 'min:1'],
            'compositions.*.marks_per_question' => ['required', 'numeric', 'min:0.1'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $exam) {
            $currentSessionId = $exam->academic_session_id;
            $dto = ExamDTO::fromRequest($request, $currentSessionId);

            // Sync branch slug
            $school = \App\Models\School::find($request->school_id);
            $data = $dto->toArray();
            $data['branch'] = $school?->slug;

            // Remove compositions from array before update as it's a relationship
            $compositions = $data['compositions'];
            unset($data['compositions']);

            // If compositions is empty, ensure subject_id is NOT null
            if (empty($compositions) && is_null($data['subject_id'])) {
                $data['subject_id'] = $exam->subject_id;
            }

            $exam->update($data);

            if (! empty($compositions)) {
                // Wipe and recreate compositions for simplicity in sync
                $exam->compositions()->delete();
                foreach ($compositions as $compDto) {
                    $exam->compositions()->create($compDto->toArray());
                }
            } else {
                $exam->compositions()->delete();
            }
        });

        return redirect()->route('staff.exams.show', $exam->id)
            ->with('success', 'Exam updated successfully.');
    }

    /**
     * Delete the exam.
     */
    public function destroy(Exam $exam): \Illuminate\Http\RedirectResponse
    {
        // Safety: Prevent deletion if there are already attempts
        if ($exam->attempts()->exists()) {
            return back()->with('error', 'Cannot delete an exam that already has student attempts.');
        }

        $exam->questions()->detach();
        $exam->delete();

        return redirect()->route('staff.exams.index')
            ->with('success', 'Exam deleted successfully.');
    }

    /**
     * Display a listing of exam results.
     */
    public function results(Request $request): \Inertia\Response
    {
        $user = $request->user();

        $query = Exam::with(['subject', 'schoolClass'])
            ->withCount('attempts');

        if ($request->school_id) {
            $query->where('school_id', $request->school_id);
        }

        if (! $user->can('sys:manage_settings')) {
            // Staff are ALWAYS scoped to their assigned school branch
            $query->where('school_id', $user->school_id);
        }

        return Inertia::render('Staff/Results/Index', [
            'exams' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['school_id']),
        ]);
    }

    /**
     * Show detailed results for a specific exam.
     */
    public function showResults(Exam $exam): \Inertia\Response
    {
        $exam->load(['subject', 'schoolClass']);

        $attempts = $exam->attempts()
            ->with(['user.schoolClass'])
            ->latest('submitted_at')
            ->get();

        return Inertia::render('Staff/Results/Show', [
            'exam' => $exam,
            'attempts' => $attempts,
            'totalQuestions' => $exam->questions()->count(),
        ]);
    }

    /**
     * Show specific student result details.
     */
    public function showStudentResult(Exam $exam, \App\Models\User $student): \Inertia\Response
    {
        $exam->load(['subject', 'schoolClass']);

        $attempt = $exam->attempts()
            ->where('user_id', $student->id)
            ->with(['answers.question.options', 'answers.option'])
            ->firstOrFail();

        return Inertia::render('Staff/Results/StudentDetails', [
            'exam' => $exam,
            'student' => $student,
            'attempt' => $attempt,
        ]);
    }

    /**
     * Display the official result slip for a specific student.
     */
    public function showStudentResultPrint(Exam $exam, \App\Models\User $student): \Illuminate\View\View
    {
        $exam->load(['subject', 'schoolClass', 'academicSession']);

        $attempt = $exam->attempts()
            ->where('user_id', $student->id)
            ->with(['user.schoolClass'])
            ->firstOrFail();

        return view('staff.exams.student-result-print', [
            'exam' => $exam,
            'student' => $attempt->user,
            'attempt' => $attempt,
            'totalQuestions' => $exam->questions()->count(),
        ]);
    }

    /**
     * Display the hard copy examination paper.
     */
    public function showHardCopy(Exam $exam): \Illuminate\View\View
    {
        $exam->load([
            'school',
            'subject',
            'schoolClass',
            'academicSession',
            'questions' => function ($query) {
                $query->with(['options', 'topic.subject'])->orderByPivot('order', 'asc');
            },
        ]);

        return view('staff.exams.print', [
            'exam' => $exam,
        ]);
    }

    /**
     * Display the examination answer sheet.
     */
    public function showAnswerSheet(Exam $exam): \Illuminate\View\View
    {
        $exam->load([
            'school',
            'subject',
            'schoolClass',
            'academicSession',
            'questions' => function ($query) {
                $query->orderByPivot('order', 'asc');
            },
        ]);

        return view('staff.exams.answer-sheet', [
            'exam' => $exam,
        ]);
    }

    /**
     * Display the official results printout for an exam.
     */
    public function showResultsPrint(Exam $exam): \Illuminate\View\View
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

        return view('staff.exams.results-print', [
            'exam' => $exam,
            'totalQuestions' => $exam->questions()->count(),
        ]);
    }
}
