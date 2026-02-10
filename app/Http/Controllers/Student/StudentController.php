<?php

namespace App\Http\Controllers\Student;

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
        $redirectUrl = $this->authService->login($request->credentials(), $request->boolean('remember'), 'student');

        return redirect()->intended($redirectUrl);
    }

    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        $currentSession = AcademicSession::current()->first();

        $exams = [];
        if ($currentSession) {
            $exams = Exam::where('academic_session_id', $currentSession->id)
                ->where('school_class_id', $user->school_class_id)
                ->where('status', \App\Enums\ExamStatus::LIVE)
                ->with(['subject'])
                ->withCount('questions')
                ->get();
        }

        return Inertia::render('Student/Dashboard', [
            'availableExams' => $exams,
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
    public function showExam(\App\Models\ExamAttempt $attempt): Response
    {
        // Security: Ensure the student owns this attempt
        if ($attempt->user_id !== auth()->id()) {
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
        ]);
    }

    /**
     * Submit the exam.
     */
    public function submitExam(Request $request, \App\Models\ExamAttempt $attempt): RedirectResponse
    {
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $this->examService->submitAttempt($attempt, $request->array('answers'));

        return redirect()->route('student.exams.result', $attempt->id);
    }

    /**
     * Show the exam result.
     */
    public function showResult(\App\Models\ExamAttempt $attempt): Response
    {
        if ($attempt->user_id !== auth()->id()) {
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
