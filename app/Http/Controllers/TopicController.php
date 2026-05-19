<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function index(Request $request): Response
    {
        $topics = Topic::query()
            ->with('subject')
            ->withCount('questions')
            ->orderBy('name')
            ->paginate(20);

        $subjects = Subject::query()->orderBy('name')->get(['id', 'name', 'level']);

        return Inertia::render('Topics/Index', [
            'topics' => $topics,
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
            'name' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'description' => 'nullable|string',
        ]);

        Topic::query()->create([
            'name' => $validated['name'],
            'slug' => str($validated['name'])->slug(),
            'subject_id' => $validated['subject_id'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Topic created successfully.');
    }

    public function update(Request $request, Topic $topic): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'description' => 'nullable|string',
        ]);

        $topic->update([
            'name' => $validated['name'],
            'slug' => str($validated['name'])->slug(),
            'subject_id' => $validated['subject_id'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Topic updated successfully.');
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        if ($topic->questions()->exists()) {
            return back()->with('error', 'Cannot delete topic with existing questions.');
        }

        $topic->delete();

        return back()->with('success', 'Topic deleted successfully.');
    }
}
