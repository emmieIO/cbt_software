<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:multiple_choice,theory',
            'topic_id' => 'required|exists:topics,id',
            'content' => 'required|string',
            'level' => 'required|in:lp,hp,js,ss',
            'explanation' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('questions', 'public');
        }

        $markingScheme = $request->input('marking_scheme');
        if (is_string($markingScheme)) {
            $markingScheme = json_decode($markingScheme, true);
        }

        $options = $request->input('options');
        if (is_string($options)) {
            $options = json_decode($options, true);
        }

        $question = Question::query()->create([
            'topic_id' => $validated['topic_id'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'level' => $validated['level'],
            'explanation' => $validated['explanation'] ?? null,
            'image_path' => $imagePath,
            'marking_scheme' => $validated['type'] === 'theory' ? ($markingScheme ?? []) : null,
            'created_by' => $request->user()->id,
        ]);

        if ($validated['type'] === 'multiple_choice' && $options) {
            foreach ($options as $option) {
                $question->options()->create([
                    'content' => $option['content'],
                    'is_correct' => $option['is_correct'] ?? false,
                ]);
            }
        }

        return to_route('questions.index')->with('success', 'Question created successfully.');
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:multiple_choice,theory',
            'topic_id' => 'required|exists:topics,id',
            'content' => 'required|string',
            'level' => 'required|in:lp,hp,js,ss',
            'explanation' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'remove_image' => 'nullable|boolean',
        ]);

        $imagePath = $question->image_path;
        if ($request->hasFile('image')) {
            if ($imagePath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('questions', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($imagePath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        }

        $markingScheme = $request->input('marking_scheme');
        if (is_string($markingScheme)) {
            $markingScheme = json_decode($markingScheme, true);
        }

        $options = $request->input('options');
        if (is_string($options)) {
            $options = json_decode($options, true);
        }

        $question->update([
            'topic_id' => $validated['topic_id'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'level' => $validated['level'],
            'explanation' => $validated['explanation'] ?? null,
            'image_path' => $imagePath,
            'marking_scheme' => $validated['type'] === 'theory' ? ($markingScheme ?? []) : null,
        ]);

        if ($validated['type'] === 'multiple_choice' && $options) {
            $question->options()->delete();
            foreach ($options as $option) {
                $question->options()->create([
                    'content' => $option['content'],
                    'is_correct' => $option['is_correct'] ?? false,
                ]);
            }
        }

        return to_route('questions.index')->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return to_route('questions.index')->with('success', 'Question deleted successfully.');
    }
}
