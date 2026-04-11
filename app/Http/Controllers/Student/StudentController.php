<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\AuthService;
use App\Services\ExamService;
use App\Services\Student\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected ExamService $examService,
        protected StudentPortalService $studentPortalService
    ) {}

    public function login(): Response
    {
        return Inertia::render('Student/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // Allow both regular students and entrance candidates to login via this portal
        $user = $this->authService->login($request->credentials(), $request->boolean('remember'), 'access:student-portal', $request);

        return redirect()->intended(route('student.dashboard'));
    }

    public function dashboard(Request $request): Response
    {
        $dashboardData = $this->studentPortalService->getDashboardData($request->user());

        return Inertia::render('Student/Dashboard', [
            'upcomingExams' => $dashboardData['upcomingExams'],
            'recentResults' => $dashboardData['recentResults'],
            'stats' => $dashboardData['stats'],
        ]);
    }

    /**
     * List all available exams.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Student/Exams/Index', [
            'exams' => $this->studentPortalService->getAvailableExams($request->user()),
        ]);
    }

    /**
     * Show student results history.
     */
    public function results(Request $request): Response
    {
        return Inertia::render('Student/Results/Index', [
            'attempts' => $this->studentPortalService->getResultsHistory($request->user()),
        ]);
    }

    /**
     * Start an exam attempt.
     */
    public function startExam(Request $request, Exam $exam): RedirectResponse
    {
        try {
            $attempt = $this->examService->startExam($request->user(), $exam);

            return redirect()->route('student.exams.show', $attempt->id);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the exam questions for the attempt.
     */
    public function showExam(Request $request, ExamAttempt $attempt): Response|RedirectResponse
    {
        // Security: Ensure the candidate owns this attempt
        if ($attempt->user_id !== $request->user()->id) {
            abort(403);
        }

        // If already submitted, redirect to result
        if ($attempt->status === AttemptStatus::SUBMITTED) {
            return redirect()->route('student.exams.result', $attempt->id);
        }

        $questions = $this->examService->getAttemptQuestions($attempt);

        return Inertia::render('Student/Exams/Show', [
            'attempt' => $attempt->load(['exam.subject']),
            'questions' => $questions,
            'savedAnswers' => $attempt->metadata['saved_answers'] ?? [],
        ]);
    }

    /**
     * Save a single answer during an ongoing attempt.
     */
    public function saveAnswer(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        if ($attempt->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($attempt->status !== AttemptStatus::ONGOING) {
            return back()->with('error', 'Exam attempt is not active.');
        }

        if ($this->examService->attemptHasTimedOut($attempt)) {
            return back()->with('error', 'Time is up for this examination. Please submit your attempt.');
        }

        $request->validate([
            'question_id' => ['required', 'string'],
            'option_id' => ['required', 'string'],
        ]);

        $questionId = $request->string('question_id')->toString();
        $optionId = $request->string('option_id')->toString();

        if (! $this->studentPortalService->isValidAttemptAnswerSelection($attempt, $questionId, $optionId)) {
            return back()->with('error', 'Invalid answer selection for this examination.');
        }

        $this->studentPortalService->saveAnswer($attempt, $questionId, $optionId);

        return back();
    }

    /**
     * Submit the exam.
     */
    public function submitExam(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        if ($attempt->user_id !== $request->user()->id) {
            abort(403);
        }

        $this->examService->submitAttempt(
            $attempt,
            $request->array('answers'),
            $request->only(['termination_reason']),
            $request->array('violations')
        );

        return redirect()->route('student.exams.result', $attempt->id);
    }

    /**
     * Show the exam result.
     */
    public function showResult(Request $request, ExamAttempt $attempt): Response
    {
        if ($attempt->user_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('Student/Exams/Result', [
            'attempt' => $attempt->load(['exam.subject']),
            'totalQuestions' => $attempt->exam->questions()->count(),
        ]);
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('home');
    }
}
