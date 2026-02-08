<?php

namespace App\Http\Controllers\Staff;

use App\DTOs\QuestionDTO;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Question\BulkDestroyQuestionRequest;
use App\Http\Requests\Question\GetQuestionsRequest;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\BulkExportService;
use App\Services\BulkImportService;
use App\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffQuestionController extends Controller
{
    public function __construct(
        protected QuestionService $questionService,
        protected BulkImportService $bulkImportService,
        protected BulkExportService $bulkExportService
    ) {}

    /**
     * Display the question bank repository.
     */
    public function index(GetQuestionsRequest $request): Response
    {
        return Inertia::render('Staff/Questions/Index', [
            'questions' => $this->questionService->getFilteredQuestions($request->validated()),
            'subjects' => Subject::all(),
            'classes' => SchoolClass::all(),
            'difficulties' => collect(QuestionDifficulty::cases())->map(fn ($d) => ['value' => $d->value, 'label' => Str::title($d->value)]),
            'filters' => $request->only(['search', 'subject_id', 'school_class_id', 'difficulty']),
        ]);
    }

    /**
     * Show the AI question generation lab.
     */
    public function generate(): Response
    {
        return Inertia::render('Staff/Questions/Generate', [
            'subjects' => Subject::with('topics')->get(),
            'classes' => SchoolClass::all(),
            'types' => collect(QuestionType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => str_replace('_', ' ', Str::title($t->value))]),
            'difficulties' => collect(QuestionDifficulty::cases())->map(fn ($d) => ['value' => $d->value, 'label' => Str::title($d->value)]),
        ]);
    }

    /**
     * Process the AI question generation.
     */
    public function processGeneration(Request $request): RedirectResponse
    {
        $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'topic_id' => ['required', 'exists:topics,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
            'difficulty' => ['required', 'string'],
        ]);

        \App\Jobs\GenerateQuestionsJob::dispatch(
            $request->user()->id,
            $request->subject_id,
            $request->topic_id,
            $request->school_class_id,
            $request->count,
            $request->difficulty
        );

        return redirect()->route('staff.questions.index')->with('success', 'AI generation has started in the background. Your questions will appear in the bank shortly.');
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(): Response
    {
        return Inertia::render('Staff/Questions/Create', [
            'subjects' => Subject::with('topics')->get(),
            'classes' => SchoolClass::all(),
            'types' => collect(QuestionType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => str_replace('_', ' ', Str::title($t->value))]),
            'difficulties' => collect(QuestionDifficulty::cases())->map(fn ($d) => ['value' => $d->value, 'label' => Str::title($d->value)]),
        ]);
    }

    /**
     * Store a new question.
     */
    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $dto = QuestionDTO::fromRequest($request);
        $this->questionService->createQuestion($dto, $request->user()->id);

        return redirect()->route('staff.questions.index')->with('success', 'Question created successfully.');
    }

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

    /**
     * Toggle question status.
     */
    public function toggleStatus(Question $question): RedirectResponse
    {
        $question->update(['is_active' => ! $question->is_active]);

        return back()->with('success', 'Question status updated successfully.');
    }

    /**
     * Delete a question.
     */
    public function destroy(Question $question): RedirectResponse
    {
        $this->questionService->deleteQuestion($question);

        return redirect()->route('staff.questions.index')->with('success', 'Question deleted successfully.');
    }

    /**
     * Bulk delete questions.
     */
    public function bulkDestroy(BulkDestroyQuestionRequest $request): RedirectResponse
    {
        $count = $this->questionService->bulkDeleteQuestions($request->ids);

        return redirect()->route('staff.questions.index')->with('success', "$count questions deleted successfully.");
    }
}
