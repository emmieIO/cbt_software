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
            $assignedClassIds = $user->currentAssignments()->pluck('school_class_id')->unique();
            $assignedSubjectIds = $user->currentAssignments()->pluck('subject_id')->unique();

            $query->where(function ($q) use ($assignedClassIds, $assignedSubjectIds) {
                $q->whereIn('school_class_id', $assignedClassIds)
                    ->whereIn('subject_id', $assignedSubjectIds)
                    ->orWhere('type', \App\Enums\ExamType::ENTRANCE); // Staff can see all entrance exams for setup
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

        // Get only assigned loads for this teacher
        $assignments = $user->currentAssignments()
            ->with(['schoolClass', 'subject', 'prospectiveClass'])
            ->get();

        // Get prospective batches for entrance exams
        $batches = \App\Models\ProspectiveClass::where('is_active', true)->get();

        return Inertia::render('Staff/Exams/Create', [
            'assignments' => $assignments,
            'sessions' => AcademicSession::current()->get(),
            'batches' => $batches,
            'subjects' => \App\Models\Subject::all(),
            'classes' => \App\Models\SchoolClass::all(),
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
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
            'prospective_class_id' => ['nullable', 'exists:prospective_classes,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string'], // ExamType enum
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
        ]);

        if (! $request->school_class_id && ! $request->prospective_class_id) {
            return back()->withErrors(['school_class_id' => 'Please select a target class or batch.']);
        }

        $currentSession = AcademicSession::current()->firstOrFail();

        // If it's an entrance exam, ensure prospective_class_id is set
        if ($request->type === 'entrance' && ! $request->prospective_class_id) {
            return back()->withErrors(['prospective_class_id' => 'Entrance exams must be assigned to a prospective batch.']);
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

        // Get only assigned loads for this teacher

        $assignments = $user->currentAssignments()

            ->with(['schoolClass', 'subject'])

            ->get();

        // Get prospective batches for entrance exams

        $batches = \App\Models\ProspectiveClass::where('is_active', true)->get();

        return Inertia::render('Staff/Exams/Edit', [

            'exam' => $exam->load(['subject', 'schoolClass', 'prospectiveClass']),

            'assignments' => $assignments,

            'sessions' => AcademicSession::current()->get(),

            'batches' => $batches,

            'subjects' => \App\Models\Subject::all(),

            'classes' => \App\Models\SchoolClass::all(),

        ]);

    }

    /**
     * Update the exam.
     */
    public function update(Request $request, Exam $exam): RedirectResponse
    {

        $request->validate([

            'title' => ['required', 'string', 'max:255'],

            'subject_id' => ['required', 'exists:subjects,id'],

            'school_class_id' => ['nullable', 'exists:school_classes,id'],

            'prospective_class_id' => ['nullable', 'exists:prospective_classes,id'],

            'duration' => ['required', 'integer', 'min:1'],

            'type' => ['required', 'string'], // ExamType enum

            'start_time' => ['nullable', 'date'],

            'end_time' => ['nullable', 'date', 'after:start_time'],

            'status' => ['required', 'string'], // ExamStatus enum

        ]);

        if (! $request->school_class_id && ! $request->prospective_class_id) {

            return back()->withErrors(['school_class_id' => 'Please select a target class or batch.']);

        }

        $exam->update($request->all());

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

            $assignedClassIds = $user->currentAssignments()->pluck('school_class_id')->unique();

            $assignedSubjectIds = $user->currentAssignments()->pluck('subject_id')->unique();

            $query->where(function ($q) use ($assignedClassIds, $assignedSubjectIds) {

                $q->whereIn('school_class_id', $assignedClassIds)
                    ->whereIn('subject_id', $assignedSubjectIds);

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
}
