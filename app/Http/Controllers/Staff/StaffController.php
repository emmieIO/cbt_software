<?php

namespace App\Http\Controllers\Staff;

use App\DTOs\QuestionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\AuthService;
use App\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected QuestionService $questionService
    ) {}

    public function login(): Response
    {
        return Inertia::render('Staff/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $redirectUrl = $this->authService->login($request->credentials(), $request->boolean('remember'));

        return redirect()->intended($redirectUrl);
    }

    public function dashboard(): Response
    {
        return Inertia::render('Staff/Dashboard');
    }

    /**
     * Display the question bank repository.
     */
    public function index(): Response
    {
        return Inertia::render('Staff/Questions/Index', [
            'questions' => \App\Models\Question::with(['topic.subject', 'schoolClass', 'options'])
                ->latest()
                ->paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(): Response
    {
        return Inertia::render('Staff/Questions/Create', [
            'subjects' => Subject::with('topics')->get(),
            'classes' => SchoolClass::all(),
            'types' => collect(\App\Enums\QuestionType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => str_replace('_', ' ', \Illuminate\Support\Str::title($t->value))]),
            'difficulties' => collect(\App\Enums\QuestionDifficulty::cases())->map(fn ($d) => ['value' => $d->value, 'label' => \Illuminate\Support\Str::title($d->value)]),
        ]);
    }

    /**
     * Store a new question.
     */
    public function storeQuestion(StoreQuestionRequest $request): RedirectResponse
    {
        $dto = QuestionDTO::fromRequest($request);
        $this->questionService->createQuestion($dto, $request->user()->id);

        return redirect()->route('staff.questions.index')->with('success', 'Question created successfully.');
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('home');
    }
}
