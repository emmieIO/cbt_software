<?php

namespace App\Http\Controllers;

use App\Http\Requests\Questions\BulkStoreQuestionRequest;
use App\Http\Requests\Questions\SaveQuestionRequest;
use App\Models\Question;
use App\Models\Subject;
use App\Services\QuestionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function __construct(private readonly QuestionService $questionService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'subject_id', 'level', 'overused']);
        $perPage = (int) $request->input('per_page', 15);

        $questions = Question::query()
            ->with(['topic.subject', 'creator', 'options'])
            ->when($filters['search'] ?? null, fn (Builder $q, $s) => $q->where('content', 'like', "%{$s}%"))
            ->when($filters['subject_id'] ?? null, fn (Builder $q, $id) => $q->whereHas('topic', fn ($t) => $t->where('subject_id', $id)))
            ->when($filters['level'] ?? null, fn (Builder $q, $l) => $q->where('level', $l))
            ->when($filters['overused'] ?? null, fn (Builder $q) => $q->frequentlyUsed())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Questions/Index', [
            'questions' => $questions,
            'subjects' => $subjects,
            'filters' => $filters,
            'levels' => [
                ['value' => 'lp', 'label' => 'Lower Primary'],
                ['value' => 'hp', 'label' => 'Higher Primary'],
                ['value' => 'js', 'label' => 'Junior Secondary'],
                ['value' => 'ss', 'label' => 'Senior Secondary'],
            ],
        ]);
    }

    public function create(): Response
    {
        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Questions/Create', [
            'subjects' => $subjects,
            'levels' => [
                ['value' => 'lp', 'label' => 'Lower Primary'],
                ['value' => 'hp', 'label' => 'Higher Primary'],
                ['value' => 'js', 'label' => 'Junior Secondary'],
                ['value' => 'ss', 'label' => 'Senior Secondary'],
            ],
        ]);
    }

    public function edit(Question $question): Response
    {
        $question->load(['topic.subject', 'options']);

        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Questions/Edit', [
            'question' => $question,
            'subjects' => $subjects,
            'levels' => [
                ['value' => 'lp', 'label' => 'Lower Primary'],
                ['value' => 'hp', 'label' => 'Higher Primary'],
                ['value' => 'js', 'label' => 'Junior Secondary'],
                ['value' => 'ss', 'label' => 'Senior Secondary'],
            ],
        ]);
    }

    public function store(SaveQuestionRequest $request): RedirectResponse
    {
        $this->questionService->create($request->payload(), $request->user()->id);

        return to_route('questions.index')->with('success', 'Question created successfully.');
    }

    public function bulkStore(BulkStoreQuestionRequest $request): RedirectResponse
    {
        $count = $this->questionService->bulkCreate($request->questions(), $request->user()->id);

        return to_route('questions.index')->with('success', $count.' questions created successfully.');
    }

    public function update(SaveQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->questionService->update($question, $request->payload());

        return to_route('questions.index')->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return to_route('questions.index')->with('success', 'Question deleted successfully.');
    }
}
