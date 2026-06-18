<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subjects\SaveSubjectRequest;
use App\Models\Subject;
use App\Services\SubjectService;
use App\Support\AcademicLevels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function __construct(private readonly SubjectService $subjectService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['level']);

        $subjects = Subject::query()
            ->when($filters['level'] ?? null, fn ($query, $level) => $query->where('level', $level))
            ->withCount('topics')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Subjects/Index', [
            'subjects' => $subjects,
            'levels' => AcademicLevels::levelOptions(),
            'filters' => $filters,
        ]);
    }

    public function store(SaveSubjectRequest $request): RedirectResponse
    {
        $this->subjectService->create($request->payload());

        return back()->with('success', 'Subject created successfully.');
    }

    public function update(SaveSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->subjectService->update($subject, $request->payload());

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
