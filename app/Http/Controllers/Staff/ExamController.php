<?php

namespace App\Http\Controllers\Staff;

use App\DTOs\ExamDTO;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Exam;
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
        $query = Exam::with(['subject', 'schoolClass', 'academicSession', 'prospectiveClass'])
            ->withCount('questions');

        // Scoping: Staff only see their own exams or exams for their assigned loads
        if (! $user->hasRole('admin')) {
            $assignments = $user->currentAssignments()->get();
            $assignedClassIds = $assignments->pluck('school_class_id')->filter()->unique();
            $assignedSubjectIds = $assignments->pluck('subject_id')->filter()->unique();
            $assignedBatchIds = $assignments->pluck('prospective_class_id')->filter()->unique();
            $isCoordinator = $assignments->contains(fn ($a) => is_null($a->subject_id));

            $query->where(function ($q) use ($assignedClassIds, $assignedSubjectIds, $assignedBatchIds, $isCoordinator) {
                // Regular Exam Scoping
                $q->where(function ($subQ) use ($assignedClassIds, $assignedSubjectIds) {
                    $subQ->whereIn('school_class_id', $assignedClassIds)
                        ->whereIn('subject_id', $assignedSubjectIds);
                });

                // Entrance Exam Scoping
                $q->orWhere(function ($subQ) use ($assignedBatchIds, $assignedSubjectIds, $isCoordinator) {
                    $subQ->where('type', \App\Enums\ExamType::ENTRANCE)
                        ->whereIn('prospective_class_id', $assignedBatchIds);

                    // If not a coordinator, further restrict entrance exams to their subject
                    if (! $isCoordinator) {
                        $subQ->whereIn('subject_id', $assignedSubjectIds);
                    }
                });
            });
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

        // Get authorized context with strict subjects (only those explicitly assigned)
        $context = (new \App\Services\QuestionService)->getAuthorizedContext($user, false, true);

        // Get only assigned loads for this teacher
        $assignments = $user->currentAssignments()
            ->with(['schoolClass', 'subject', 'prospectiveClass'])
            ->get();

        return Inertia::render('Staff/Exams/Create', [
            'assignments' => $assignments,
            'sessions' => AcademicSession::current()->get(),
            'batches' => $context['batches'],
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
        ]);
    }

    /**
     * Store a new exam.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'branch' => ['required', 'string', \Illuminate\Validation\Rule::enum(\App\Enums\Branch::class)],
            'subject_id' => ['required_unless:type,entrance', 'nullable', 'exists:subjects,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'prospective_class_id' => ['required_if:type,entrance', 'nullable', 'exists:prospective_classes,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string'], // ExamType enum
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'compositions' => ['required_if:type,entrance', 'array'],
            'compositions.*.subject_id' => ['required', 'exists:subjects,id'],
            'compositions.*.topic_id' => ['nullable', 'exists:topics,id'],
            'compositions.*.question_count' => ['required', 'integer', 'min:1'],
        ]);

        $currentSession = AcademicSession::current()->first();

        if (! $currentSession) {
            return back()->with('error', 'No current academic session found. Please contact an administrator to set an active session.');
        }

        $dto = ExamDTO::fromRequest($request, $currentSession->id);
        $exam = $this->examService->createExam($dto->toArray(), $request->user()->id);

        return redirect()->route('staff.exams.show', $exam->id)
            ->with('success', 'Exam configuration saved. Now allocate your questions.');
    }

    /**
     * Show exam details.
     */
    public function show(Exam $exam): Response
    {
        $exam->load(['subject', 'schoolClass', 'prospectiveClass', 'questions']);

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

        return back()->with('success', 'AI has balanced and selected '.$count.' questions following the biennial rotation policy.');

    }

    /**
     * Show the edit form.
     */
    public function edit(Request $request, Exam $exam): Response
    {
        $user = $request->user();

        // Get authorized context with strict subjects
        $context = (new \App\Services\QuestionService)->getAuthorizedContext($user, false, true);

        // Get only assigned loads for this teacher
        $assignments = $user->currentAssignments()
            ->with(['schoolClass', 'subject', 'prospectiveClass'])
            ->get();

        return Inertia::render('Staff/Exams/Edit', [
            'exam' => $exam->load(['subject', 'schoolClass', 'prospectiveClass', 'compositions']),
            'assignments' => $assignments,
            'sessions' => AcademicSession::current()->get(),
            'batches' => $context['batches'],
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
        ]);
    }

    /**
     * Update the exam.
     */
    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'branch' => ['required', 'string', \Illuminate\Validation\Rule::enum(\App\Enums\Branch::class)],
            'subject_id' => ['required_unless:type,entrance', 'nullable', 'exists:subjects,id'],
            'school_class_id' => ['required_unless:type,entrance', 'nullable', 'exists:school_classes,id'],
            'prospective_class_id' => ['required_if:type,entrance', 'nullable', 'exists:prospective_classes,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string'], // ExamType enum
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'status' => ['required', 'string'], // ExamStatus enum
            'compositions' => ['required_if:type,entrance', 'array'],
            'compositions.*.subject_id' => ['required', 'exists:subjects,id'],
            'compositions.*.topic_id' => ['nullable', 'exists:topics,id'],
            'compositions.*.question_count' => ['required', 'integer', 'min:1'],
            'compositions.*.marks_per_question' => ['required', 'numeric', 'min:0.1'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $exam) {
            $data = $request->only([
                'title', 'branch', 'subject_id', 'school_class_id', 'prospective_class_id',
                'duration', 'type', 'start_time', 'end_time', 'status',
            ]);

            $exam->update($data);

            if ($request->type === 'entrance') {
                // Wipe and recreate compositions for simplicity in sync
                $exam->compositions()->delete();
                foreach ($request->compositions as $comp) {
                    $exam->compositions()->create([
                        'subject_id' => $comp['subject_id'],
                        'topic_id' => $comp['topic_id'] ?? null,
                        'question_count' => $comp['question_count'],
                        'marks_per_question' => $comp['marks_per_question'],
                    ]);
                }
            }
        });

        return redirect()->route('staff.exams.show', $exam->id)
            ->with('success', 'Exam updated successfully.');
    }

    /**
     * Delete the exam.
     */
    public function destroy(Exam $exam): RedirectResponse
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
    public function results(Request $request): Response
    {

        $user = $request->user();

        $query = Exam::with(['subject', 'schoolClass', 'prospectiveClass'])

            ->withCount('attempts');

        if (! $user->hasRole('admin')) {
            $assignments = $user->currentAssignments()->get();
            $assignedClassIds = $assignments->pluck('school_class_id')->filter()->unique();
            $assignedSubjectIds = $assignments->pluck('subject_id')->filter()->unique();
            $assignedBatchIds = $assignments->pluck('prospective_class_id')->filter()->unique();
            $isCoordinator = $assignments->contains(fn ($a) => is_null($a->subject_id));

            $query->where(function ($q) use ($assignedClassIds, $assignedSubjectIds, $assignedBatchIds, $isCoordinator) {
                // Regular Exam results
                $q->where(function ($subQ) use ($assignedClassIds, $assignedSubjectIds) {
                    $subQ->whereIn('school_class_id', $assignedClassIds)
                        ->whereIn('subject_id', $assignedSubjectIds);
                });

                // Entrance Exam results
                $q->orWhere(function ($subQ) use ($assignedBatchIds, $assignedSubjectIds, $isCoordinator) {
                    $subQ->where('type', \App\Enums\ExamType::ENTRANCE)
                        ->whereIn('prospective_class_id', $assignedBatchIds);

                    if (! $isCoordinator) {
                        $subQ->whereIn('subject_id', $assignedSubjectIds);
                    }
                });
            });
        }

        return Inertia::render('Staff/Results/Index', [

            'exams' => $query->latest()->paginate(10),

        ]);

    }

    /**
     * Show detailed results for a specific exam.
     */
    public function showResults(Exam $exam): Response
    {

        $exam->load(['subject', 'schoolClass', 'prospectiveClass']);

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
     * Print the examination paper.
     */
    public function print(Exam $exam): \Illuminate\Contracts\View\View
    {
        $exam->load([
            'subject',
            'schoolClass',
            'prospectiveClass',
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
     * Print the examination answer sheet.
     */
    public function printAnswerSheet(Exam $exam): \Illuminate\Contracts\View\View
    {
        $exam->load([
            'subject',
            'schoolClass',
            'prospectiveClass',
            'academicSession',
            'questions' => function ($query) {
                $query->orderByPivot('order', 'asc');
            },
        ]);

        return view('staff.exams.answer-sheet', [
            'exam' => $exam,
        ]);
    }
}
