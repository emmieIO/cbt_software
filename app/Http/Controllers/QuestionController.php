<?php

namespace App\Http\Controllers;

use App\Http\Requests\Questions\BulkStoreQuestionRequest;
use App\Http\Requests\Questions\SaveQuestionRequest;
use App\Models\Question;
use App\Models\Subject;
use App\Services\QuestionService;
use App\Support\AcademicLevels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function __construct(private readonly QuestionService $questionService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'subject_id', 'level', 'class_level', 'overused', 'question_id', 'generation_run_id']);
        $perPage = (int) $request->input('per_page', 15);

        $questions = Question::query()
            ->with(['topic.subject', 'creator', 'options'])
            ->when($filters['question_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($filters['generation_run_id'] ?? null, fn (Builder $query, $id) => $query
                ->whereHas('generatedDraft', fn (Builder $draft) => $draft->where('question_generation_run_id', $id)))
            ->when($filters['search'] ?? null, fn (Builder $q, $s) => $q->where('content', 'like', "%{$s}%"))
            ->when($filters['subject_id'] ?? null, fn (Builder $q, $id) => $q->whereHas('topic', fn ($t) => $t->where('subject_id', $id)))
            ->when($filters['level'] ?? null, fn (Builder $q, $l) => $q->where('level', $l))
            ->when($filters['class_level'] ?? null, fn (Builder $q, $l) => $q->where('class_level', $l))
            ->when($filters['overused'] ?? null, fn (Builder $q) => $q->frequentlyUsed())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)->withQueryString();

        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Questions/Index', [
            'questions' => $questions,
            'subjects' => $subjects,
            'filters' => $filters,
            'levels' => AcademicLevels::levelOptions(),
            'classLevels' => AcademicLevels::classOptions(),
        ]);
    }

    public function create(): Response
    {
        abort_unless(request()->user()?->canCreateQuestions(), 403);

        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Questions/Create', [
            'subjects' => $subjects,
            'levels' => AcademicLevels::levelOptions(),
            'classLevels' => AcademicLevels::classOptions(),
        ]);
    }

    public function edit(Question $question): Response
    {
        abort_unless(request()->user()?->canEditQuestions(), 403);

        $question->load(['topic.subject', 'options']);

        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Questions/Edit', [
            'question' => $question,
            'canViewProvenance' => (request()->user()?->canGenerateQuestions() ?? false)
                && $question->generatedDraft()->whereHas('run', fn ($query) => $query->where('requested_by', request()->user()?->id))->exists(),
            'subjects' => $subjects,
            'levels' => AcademicLevels::levelOptions(),
            'classLevels' => AcademicLevels::classOptions(),
        ]);
    }

    public function provenance(Request $request, Question $question): JsonResponse
    {
        abort_unless(($request->user()?->canGenerateQuestions() ?? false)
            && $question->generatedDraft()->whereHas('run', fn ($query) => $query->where('requested_by', $request->user()?->id))->exists(), 403);

        return response()->json(['sources' => $question->provenances()->get()]);
    }

    public function store(SaveQuestionRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->canCreateQuestions(), 403);

        $this->questionService->create($request->payload(), $request->user()->id);

        return to_route('questions.index')->with('success', 'Question created successfully.');
    }

    public function bulkStore(BulkStoreQuestionRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->canCreateQuestions(), 403);

        $count = $this->questionService->bulkCreate($request->questions(), $request->user()->id);

        return to_route('questions.index')->with('success', $count.' questions created successfully.');
    }

    public function update(SaveQuestionRequest $request, Question $question): RedirectResponse
    {
        abort_unless($request->user()?->canEditQuestions(), 403);

        $this->questionService->update($question, $request->payload());

        return to_route('questions.index')->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        abort_unless(request()->user()?->canEditQuestions(), 403);

        $question->delete();

        return to_route('questions.index')->with('success', 'Question deleted successfully.');
    }
}
