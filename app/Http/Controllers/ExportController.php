<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExportController extends Controller
{
    public function index(): Response
    {
        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Export', [
            'subjects' => $subjects,
            'levels' => [
                ['value' => 'lp', 'label' => 'Lower Primary'],
                ['value' => 'hp', 'label' => 'Higher Primary'],
                ['value' => 'js', 'label' => 'Junior Secondary'],
                ['value' => 'ss', 'label' => 'Senior Secondary'],
            ],
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'level' => 'required|in:lp,hp,js,ss',
            'instructions' => 'nullable|string',
            'mcq_count' => 'required|integer|min:0|max:100',
            'theory_count' => 'required|integer|min:0|max:20',
        ]);

        $subject = Subject::query()->findOrFail($validated['subject_id']);

        $mcqs = collect();
        $theory = collect();

        if ($validated['mcq_count'] > 0) {
            $mcqs = Question::query()
                ->where('type', 'multiple_choice')
                ->where('level', $validated['level'])
                ->whereHas('topic', fn ($q) => $q->where('subject_id', $validated['subject_id']))
                ->with('options')
                ->inRandomOrder()
                ->take($validated['mcq_count'])
                ->get();
            $mcqs->each->markAsUsed();
        }

        if ($validated['theory_count'] > 0) {
            $theory = Question::query()
                ->where('type', 'theory')
                ->where('level', $validated['level'])
                ->whereHas('topic', fn ($q) => $q->where('subject_id', $validated['subject_id']))
                ->inRandomOrder()
                ->take($validated['theory_count'])
                ->get();
            $theory->each->markAsUsed();
        }

        $exam = Exam::query()->create([
            'title' => $validated['title'],
            'subject_name' => $subject->name,
            'level' => $validated['level'],
            'instructions' => $validated['instructions'] ?? 'Answer all questions carefully.',
            'mcq_count' => $mcqs->count(),
            'theory_count' => $theory->count(),
            'total_marks' => $mcqs->count() + $theory->sum(fn ($q) => collect($q->marking_scheme)->sum('weight')),
            'created_by' => $request->user()->id,
        ]);

        foreach ($mcqs as $i => $q) {
            $exam->questions()->attach($q->id, ['section' => 'mcq', 'sort_order' => $i]);
        }
        foreach ($theory as $i => $q) {
            $exam->questions()->attach($q->id, ['section' => 'theory', 'sort_order' => $i]);
        }

        return to_route('exams.show', $exam);
    }
}
