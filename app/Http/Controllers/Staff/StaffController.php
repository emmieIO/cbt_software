<?php

namespace App\Http\Controllers\Staff;

use App\DTOs\QuestionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\AuthService;
use App\Services\BulkExportService;
use App\Services\BulkImportService;
use App\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected QuestionService $questionService,
        protected BulkImportService $bulkImportService,
        protected BulkExportService $bulkExportService
    ) {}

    /**
     * Bulk import questions from CSV or Excel.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $count = $this->bulkImportService->import($request->file('file'), $request->user()->id);

        return redirect()->route('staff.questions.index')->with('success', "$count questions imported successfully.");
    }

    /**
     * Export the question bank.
     */
    public function export(): StreamedResponse
    {
        return $this->bulkExportService->export();
    }

    /**
     * Download import template.
     */
    public function downloadTemplate(): StreamedResponse
    {
        return $this->bulkExportService->downloadTemplate();
    }

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
    public function index(\Illuminate\Http\Request $request): Response
    {
        $query = \App\Models\Question::query()
            ->with(['topic.subject', 'schoolClass', 'options'])
            ->latest();

        if ($request->filled('search')) {
            $query->where('content', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('subject_id')) {
            $query->whereHas('topic', function ($q) use ($request) {
                $q->where('subject_id', $request->subject_id);
            });
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        return Inertia::render('Staff/Questions/Index', [
            'questions' => $query->paginate(10)->withQueryString(),
            'subjects' => Subject::all(),
            'classes' => SchoolClass::all(),
            'difficulties' => collect(\App\Enums\QuestionDifficulty::cases())->map(fn ($d) => ['value' => $d->value, 'label' => \Illuminate\Support\Str::title($d->value)]),
            'filters' => $request->only(['search', 'subject_id', 'school_class_id', 'difficulty']),
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
