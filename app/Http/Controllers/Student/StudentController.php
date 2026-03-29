<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Services\AuthService;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected ExamService $examService
    ) {}

    public function login(): Response
    {
        return Inertia::render('Student/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // Allow both regular students and entrance candidates to login via this portal
        $user = $this->authService->login($request->credentials(), $request->boolean('remember'), 'access:student-portal');

        return redirect()->intended(route('student.dashboard'));
    }

    public function dashboard(Request $request): Response
    {
        $user = $request->user(); // Use default 'web' guard
        $currentSession = AcademicSession::current()->first();

        $exams = [];
        if ($currentSession) {
            $query = Exam::query()->where('academic_session_id', $currentSession->id)
                ->where('status', ExamStatus::LIVE)
                ->with(['subject'])
                ->with(['attempts' => fn ($q) => $q->where('user_id', $user->id)])
                ->withCount('questions');

            // All students are 'candidate' role now
            if ($user->status === 'candidate') {
                // Entrance Candidates see Entrance Exams for their assigned batch
                $query->where('type', ExamType::ENTRANCE)
                    ->where('prospective_class_id', $user->prospective_class_id);
            } else {
                // Regular students see exams for their current class
                $query->where('school_class_id', $user->school_class_id);
            }

            $exams = $query->take(4)->get(); // Show top 4 on dashboard
        }

        return Inertia::render('Student/Dashboard', [
            'availableExams' => $exams,
            'completedExamsCount' => \App\Models\ExamAttempt::query()->where('user_id', $user->id)
                ->where('status', \App\Enums\AttemptStatus::SUBMITTED)
                ->count(),
        ]);
    }

    /**
     * List all available exams.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentSession = AcademicSession::current()->first();

        $exams = [];
        if ($currentSession) {
            $query = Exam::query()->where('academic_session_id', $currentSession->id)
                ->where('status', ExamStatus::LIVE)
                ->with(['subject'])
                ->with(['attempts' => fn ($q) => $q->where('user_id', $user->id)])
                ->withCount('questions');

            if ($user->status === 'candidate') {
                $query->where('type', ExamType::ENTRANCE)
                    ->where('prospective_class_id', $user->prospective_class_id);
            } else {
                $query->where('school_class_id', $user->school_class_id);
            }

            $exams = $query->get();
        }

        return Inertia::render('Student/Exams/Index', [
            'exams' => $exams,
        ]);
    }

    /**
     * Show student results history.
     */
    public function results(Request $request): Response
    {
        $attempts = \App\Models\ExamAttempt::query()->where('user_id', $request->user()->id)
            ->where('status', \App\Enums\AttemptStatus::SUBMITTED)
            ->with(['exam.subject'])
            ->latest('submitted_at')
            ->get();

        return Inertia::render('Student/Results/Index', [
            'attempts' => $attempts,
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
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the exam questions for the attempt.
     */
    public function showExam(Request $request, \App\Models\ExamAttempt $attempt): Response|RedirectResponse
    {
        // Security: Ensure the candidate owns this attempt
        if ($attempt->user_id !== $request->user()->id) {
            abort(403);
        }

        // If already submitted, redirect to result
        if ($attempt->status === \App\Enums\AttemptStatus::SUBMITTED) {
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
    public function saveAnswer(Request $request, \App\Models\ExamAttempt $attempt): RedirectResponse
    {
        if ($attempt->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($attempt->status !== \App\Enums\AttemptStatus::ONGOING) {
            return back()->with('error', 'Exam attempt is not active.');
        }

        $request->validate([
            'question_id' => ['required', 'string'],
            'option_id' => ['required', 'string'],
        ]);

        $metadata = $attempt->metadata;
        $savedAnswers = $metadata['saved_answers'] ?? [];
        $savedAnswers[$request->question_id] = $request->option_id;

        $metadata['saved_answers'] = $savedAnswers;
        $attempt->update(['metadata' => $metadata]);

        return back();
    }

    /**
     * Submit the exam.
     */
    public function submitExam(Request $request, \App\Models\ExamAttempt $attempt): RedirectResponse
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
    public function showResult(Request $request, \App\Models\ExamAttempt $attempt): Response
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
