<?php

namespace App\Http\Controllers\Staff;

use App\DTOs\QuestionDTO;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Question\BulkDestroyQuestionRequest;
use App\Http\Requests\Question\GenerateQuestionsRequest;
use App\Http\Requests\Question\GetQuestionsRequest;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Http\Requests\Question\UpdateQuestionRequest;
use App\Models\Question;
use App\Services\BulkExportService;
use App\Services\BulkImportService;
use App\Services\QuestionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffQuestionController extends Controller
{
    use AuthorizesRequests;

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
        $user = $request->user();
        $context = $this->questionService->getAuthorizedContext($user);

        return Inertia::render('QuestionBank/Index', [
            'questions' => $this->questionService->getFilteredQuestions($request->validated(), $user),
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
            'difficulties' => collect(QuestionDifficulty::cases())->map(fn ($d) => ['value' => $d->value, 'label' => Str::title($d->value)]),
            'filters' => $request->only(['search', 'subject_id', 'school_class_id', 'difficulty']),
        ]);
    }

    /**
     * Show the AI question generation lab.
     */
    public function generate(Request $request): Response
    {
        $user = $request->user();
        $context = $this->questionService->getAuthorizedContext($user, true);

        return Inertia::render('QuestionBank/Generate', [
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
            'types' => collect(QuestionType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => str_replace('_', ' ', Str::title($t->value))]),
            'difficulties' => collect(QuestionDifficulty::cases())->map(fn ($d) => ['value' => $d->value, 'label' => Str::title($d->value)]),
        ]);
    }

    /**
     * Process the AI question generation.
     */
    public function processGeneration(GenerateQuestionsRequest $request): RedirectResponse
    {
        \App\Jobs\GenerateQuestionsJob::dispatch(
            $request->user()->id,
            $request->validated('subject_id'),
            $request->validated('topic_id'),
            $request->validated('school_class_id'),
            $request->validated('count'),
            $request->validated('difficulty')
        );

        return redirect()->route('staff.questions.index')->with('success', 'AI generation has started in the background. Your questions will appear in the bank shortly.');
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        $context = $this->questionService->getAuthorizedContext($user, true);

        return Inertia::render('QuestionBank/Create', [
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
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
     * Show the form for editing the specified question.
     */
    public function edit(Request $request, Question $question): Response
    {
        $this->authorize('update', $question);

        $user = $request->user();
        $question->load(['topic.subject', 'options']);
        $context = $this->questionService->getAuthorizedContext($user, true);

        return Inertia::render('QuestionBank/Edit', [
            'question' => $question,
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
            'types' => collect(QuestionType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => str_replace('_', ' ', Str::title($t->value))]),
            'difficulties' => collect(QuestionDifficulty::cases())->map(fn ($d) => ['value' => $d->value, 'label' => Str::title($d->value)]),
        ]);
    }

    /**
     * Update the specified question in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->authorize('update', $question);

        $dto = QuestionDTO::fromRequest($request);
        $this->questionService->updateQuestion($question, $dto, $request->user()->id);

        return redirect()->route('staff.questions.index')->with('success', 'Question updated successfully.');
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
     * Delete a question.
     */
    public function destroy(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $this->questionService->deleteQuestion($question);

        return redirect()->route('staff.questions.index')->with('success', 'Question deleted successfully.');
    }

    /**
     * Bulk delete questions.
     */
    public function bulkDestroy(BulkDestroyQuestionRequest $request): RedirectResponse
    {
        $count = $this->questionService->bulkDeleteQuestions($request->validated('ids'), $request->user());

        return redirect()->route('staff.questions.index')->with('success', "$count questions deleted successfully.");
    }
}
