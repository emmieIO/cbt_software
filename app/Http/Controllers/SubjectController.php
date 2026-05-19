<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(Request $request): Response
    {
        $subjects = Subject::query()
            ->withCount('topics')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Subjects/Index', [
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
            'description' => 'nullable|string',
            'level' => 'required|in:lp,hp,js,ss',
        ]);

        Subject::query()->create([
            'name' => $validated['name'],
            'slug' => str($validated['name'])->slug(),
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'],
        ]);

        return back()->with('success', 'Subject created successfully.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'required|in:lp,hp,js,ss',
        ]);

        $subject->update([
            'name' => $validated['name'],
            'slug' => str($validated['name'])->slug(),
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'],
        ]);

        return back()->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->topics()->exists()) {
            return back()->with('error', 'Cannot delete subject with existing topics.');
        }

        $subject->delete();

        return back()->with('success', 'Subject deleted successfully.');
    }
}
