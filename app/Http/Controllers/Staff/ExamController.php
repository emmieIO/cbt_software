<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\AutoSelectQuestionsRequest;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamQuestionsRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\User;
use App\Services\Exam\ExamManagementService;
use App\Services\Exam\ExamPayloadService;
use App\Services\Exam\ExamPrintService;
use App\Services\Exam\ExamReadService;
use App\Services\Exam\ExamResultService;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(
        protected ExamService $examService,
        protected ExamReadService $examReadService,
        protected ExamResultService $examResultService,
        protected ExamPrintService $examPrintService,
        protected ExamManagementService $examManagementService,
        protected ExamPayloadService $examPayloadService
    ) {}

    /**
     * Render paginated exams visible to the current staff user.
     *
     * Applies school-level visibility constraints through the read service.
     */
    public function index(Request $request): Response
    {
        $query = $this->examReadService->queryVisibleExams(
            $request->user(),
            $request->school_id
        );

        return Inertia::render('Staff/Exams/Index', [
            'exams' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['status', 'type', 'school_id']),
        ]);
    }

    /**
     * Render the exam creation screen with authorized classes and subjects.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Staff/Exams/Create', $this->examPayloadService->getFormPayload($request->user()));
    }

    /**
     * Persist a new exam for the active academic session.
     *
     * Returns a validation-style error flash when no current session exists.
     */
    public function store(StoreExamRequest $request): RedirectResponse
    {
        $currentSession = AcademicSession::current()->first();

        if (! $currentSession) {
            return back()->with('error', 'No current academic session found. Please contact an administrator to set an active session.');
        }

        $exam = $this->examManagementService->createExam(
            $request->validated(),
            $request->user()->id,
            $currentSession->id
        );

        return redirect()->route('staff.exams.show', $exam->id)
            ->with('success', 'Exam configuration saved. Now allocate your questions.');
    }

    /**
     * Render exam details and configuration summary.
     */
    public function show(Exam $exam): Response
    {
        $exam = $this->examReadService->getExamDetails($exam);

        return Inertia::render('Staff/Exams/Show', [
            'exam' => $exam,
        ]);
    }

    /**
     * Render question allocation workspace for an exam.
     */
    public function manageQuestions(Exam $exam): Response
    {
        $data = $this->examReadService->getExamQuestionManagementData($exam, $this->examService);

        return Inertia::render('Staff/Exams/Questions', [
            'exam' => $data['exam'],
            'availableQuestions' => $data['availableQuestions'],
            'selectedQuestionIds' => $data['selectedQuestionIds'],
        ]);
    }

    /**
     * Replace the exam's selected questions with validated question IDs.
     */
    public function updateQuestions(UpdateExamQuestionsRequest $request, Exam $exam): RedirectResponse
    {
        $this->examService->updateExamQuestions($exam, $request->validated('question_ids'));

        return redirect()->route('staff.exams.show', $exam->id)
            ->with('success', 'Questions allocated to the exam successfully.');
    }

    /**
     * Auto-select and attach a balanced question set for an exam.
     */
    public function aiSelectQuestions(AutoSelectQuestionsRequest $request, Exam $exam): RedirectResponse
    {
        $count = $this->examService->autoSelectQuestions($exam, (int) $request->validated('count'));

        return back()->with('success', 'AI has balanced and selected '.$count.' questions following the biennial rotation policy.');
    }

    /**
     * Render the exam edit screen with authorized payload data.
     */
    public function edit(Request $request, Exam $exam): Response
    {
        return Inertia::render('Staff/Exams/Edit', $this->examPayloadService->getEditPayload($request->user(), $exam));
    }

    /**
     * Update exam metadata using validated request payload.
     */
    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->examManagementService->updateExam($exam, $request->validated());

        return redirect()->route('staff.exams.show', $exam->id)
            ->with('success', 'Exam updated successfully.');
    }

    /**
     * Delete an exam when domain rules allow removal.
     *
     * For blocked deletions, the management service returns a reason message.
     */
    public function destroy(Exam $exam): RedirectResponse
    {
        $result = $this->examManagementService->deleteExam($exam);

        if (! $result['deleted']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('staff.exams.index')
            ->with('success', $result['message']);
    }

    /**
     * Render paginated exam results visible to the current staff user.
     */
    public function results(Request $request): Response
    {
        $query = $this->examResultService->queryVisibleResults(
            $request->user(),
            $request->school_id
        );

        return Inertia::render('Staff/Results/Index', [
            'exams' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['school_id']),
        ]);
    }

    /**
     * Render aggregated result view for a specific exam.
     */
    public function showResults(Exam $exam): Response
    {
        $resultData = $this->examResultService->getExamResults($exam);

        return Inertia::render('Staff/Results/Show', [
            'exam' => $resultData['exam'],
            'attempts' => $resultData['attempts'],
            'totalQuestions' => $resultData['totalQuestions'],
        ]);
    }

    /**
     * Render detailed result breakdown for one student in an exam.
     */
    public function showStudentResult(Exam $exam, User $student): Response
    {
        $resultData = $this->examResultService->getStudentResult($exam, $student);

        return Inertia::render('Staff/Results/StudentDetails', [
            'exam' => $resultData['exam'],
            'student' => $resultData['student'],
            'attempt' => $resultData['attempt'],
        ]);
    }

    /**
     * Render printable result slip for a student exam attempt.
     */
    public function showStudentResultPrint(Exam $exam, User $student): View
    {
        $printData = $this->examPrintService->getStudentResultPrintData($exam, $student);

        return view('staff.exams.student-result-print', [
            'exam' => $printData['exam'],
            'student' => $printData['student'],
            'attempt' => $printData['attempt'],
            'totalQuestions' => $printData['totalQuestions'],
        ]);
    }

    /**
     * Render printable hard-copy version of the exam paper.
     */
    public function showHardCopy(Exam $exam): View
    {
        $exam = $this->examPrintService->loadHardCopyData($exam);

        return view('staff.exams.print', [
            'exam' => $exam,
        ]);
    }

    /**
     * Render printable answer sheet for the exam.
     */
    public function showAnswerSheet(Exam $exam): View
    {
        $exam = $this->examPrintService->loadAnswerSheetData($exam);

        return view('staff.exams.answer-sheet', [
            'exam' => $exam,
        ]);
    }

    /**
     * Render printable class result sheet for an exam.
     */
    public function showResultsPrint(Exam $exam): View
    {
        $printData = $this->examPrintService->getResultsPrintData($exam);

        return view('staff.exams.results-print', [
            'exam' => $printData['exam'],
            'totalQuestions' => $printData['totalQuestions'],
        ]);
    }
}
