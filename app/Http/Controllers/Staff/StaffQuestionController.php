<?php

namespace App\Http\Controllers\Staff;

use App\DTOs\QuestionDTO;
use App\Enums\ClassLevel;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Question\BatchStoreQuestionRequest;
use App\Http\Requests\Question\BulkDestroyQuestionRequest;
use App\Http\Requests\Question\GenerateQuestionsRequest;
use App\Http\Requests\Question\GetQuestionsRequest;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Http\Requests\Question\UpdateQuestionRequest;
use App\Jobs\GenerateQuestionsJob;
use App\Models\Question;
use App\Services\BulkExportService;
use App\Services\BulkImportService;
use App\Services\Question\QuestionDtoFactory;
use App\Services\Question\QuestionMediaService;
use App\Services\Question\QuestionPayloadService;
use App\Services\QuestionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffQuestionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected QuestionService $questionService,
        protected BulkImportService $bulkImportService,
        protected BulkExportService $bulkExportService,
        protected QuestionMediaService $questionMediaService,
        protected QuestionPayloadService $questionPayloadService,
        protected QuestionDtoFactory $questionDtoFactory
    ) {}

    /**
     * Render question bank listing with authorized filters and pagination.
     */
    public function index(GetQuestionsRequest $request): Response
    {
        $filters = $request->validated();
        $payload = $this->questionPayloadService->getIndexPayload($request->user(), $filters);

        return Inertia::render('QuestionBank/Index', [
            ...$payload,
            'filters' => $request->only(['search', 'subject_id', 'school_class_id', 'difficulty', 'level']),
        ]);
    }

    /**
     * Render the AI-assisted question generation form.
     */
    public function generate(Request $request): Response
    {
        return Inertia::render('QuestionBank/Generate', $this->questionPayloadService->getFormPayload($request->user(), true));
    }

    /**
     * Queue asynchronous AI question generation for the current user.
     */
    public function processGeneration(GenerateQuestionsRequest $request): RedirectResponse
    {
        GenerateQuestionsJob::dispatch(
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
     * Render spreadsheet-style interface for batch question creation.
     */
    public function batchCreate(Request $request): Response
    {
        return Inertia::render('QuestionBank/BatchCreate', $this->questionPayloadService->getFormPayload($request->user(), true));
    }

    /**
     * Render setup-first bulk import page.
     */
    public function importPage(Request $request): Response
    {
        return Inertia::render('QuestionBank/Import', $this->questionPayloadService->getFormPayload($request->user(), true));
    }

    /**
     * Persist batch-created questions and attach uploaded row images.
     */
    public function batchStore(BatchStoreQuestionRequest $request): RedirectResponse
    {
        $validatedQuestions = $request->validated('questions');
        $imagePaths = [];
        $userId = $request->user()->id;

        foreach (array_keys($validatedQuestions) as $index) {
            $imagePaths[$index] = $this->questionMediaService->store($request->file("questions.$index.image"));
        }

        $dtos = $this->questionDtoFactory->makeBatch($validatedQuestions, $imagePaths);
        $this->questionService->createBatchQuestions($dtos, $userId);

        return redirect()->route('staff.questions.index')->with('success', count($dtos).' questions added successfully to the repository.');
    }

    /**
     * Render single-question creation form with contextual dropdown data.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('QuestionBank/Create', $this->questionPayloadService->getFormPayload($request->user(), true));
    }

    /**
     * Persist one question, including optional media upload.
     */
    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $imagePath = $this->questionMediaService->store($request->file('image'));

        $dto = QuestionDTO::fromRequest($request);
        $dto->image_path = $imagePath;

        $this->questionService->createQuestion($dto, $request->user()->id);

        return redirect()->route('staff.questions.index')->with('success', 'Question created successfully.');
    }

    /**
     * Render edit form for an authorized question.
     */
    public function edit(Request $request, Question $question): Response
    {
        $this->authorize('update', $question);

        return Inertia::render('QuestionBank/Edit', $this->questionPayloadService->getEditPayload($request->user(), $question));
    }

    /**
     * Update question content and reconcile media replacement/removal.
     */
    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->authorize('update', $question);

        $imagePath = $this->questionMediaService->replace(
            $request->file('image'),
            $question->image_path,
            $request->boolean('remove_image')
        );

        $dto = QuestionDTO::fromRequest($request);
        $dto->image_path = $imagePath;

        $this->questionService->updateQuestion($question, $dto, $request->user()->id);

        return redirect()->route('staff.questions.index')->with('success', 'Question updated successfully.');
    }

    /**
     * Import questions from supported spreadsheet formats.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
            'level' => ['required', 'in:'.implode(',', array_map(fn (ClassLevel $level) => $level->value, ClassLevel::cases()))],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'difficulty' => ['required', 'in:'.implode(',', array_map(fn (\App\Enums\QuestionDifficulty $difficulty) => $difficulty->value, \App\Enums\QuestionDifficulty::cases()))],
            'question_type' => ['nullable', 'in:'.implode(',', array_map(fn (QuestionType $type) => $type->value, QuestionType::cases()))],
        ]);

        $count = $this->bulkImportService->import(
            $request->file('file'),
            $request->user()->id,
            $request->only(['level', 'school_class_id', 'subject_id', 'difficulty', 'question_type'])
        );

        return redirect()->route('staff.questions.index')->with('success', "$count questions imported successfully.");
    }

    /**
     * Stream a downloadable export of the question bank.
     */
    public function export(): StreamedResponse
    {
        return $this->bulkExportService->export();
    }

    /**
     * Stream template file used for bulk question import.
     */
    public function downloadTemplate(): StreamedResponse
    {
        return $this->bulkExportService->downloadTemplate();
    }

    /**
     * Delete an authorized question and its associated media.
     */
    public function destroy(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $this->questionMediaService->delete($question->image_path);

        $this->questionService->deleteQuestion($question);

        return redirect()->route('staff.questions.index')->with('success', 'Question deleted successfully.');
    }

    /**
     * Delete multiple authorized questions and clean up their media.
     */
    public function bulkDestroy(BulkDestroyQuestionRequest $request): RedirectResponse
    {
        $questions = Question::whereIn('id', $request->validated('ids'))->get();

        foreach ($questions as $question) {
            if ($request->user()->can('delete', $question)) {
                $this->questionMediaService->delete($question->image_path);
                $this->questionService->deleteQuestion($question);
            }
        }

        return redirect()->route('staff.questions.index')->with('success', 'Selected questions deleted successfully.');
    }
}
