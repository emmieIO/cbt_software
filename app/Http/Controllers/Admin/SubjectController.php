<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function __construct(protected \App\Services\SubjectService $subjectService) {}

    /**
     * Display a listing of the subjects.
     */
    public function index(Request $request): Response
    {
        $query = Subject::withCount('topics');

        // Apply level filter if provided
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $query->latest()->paginate(10)->withQueryString(),
            'counts' => [
                'nursery' => Subject::query()->where('level', 'nursery')->count(),
                'primary' => Subject::query()->where('level', 'primary')->count(),
                'secondary' => Subject::query()->where('level', 'secondary')->count(),
            ],
            'filters' => $request->only(['level', 'search']),
        ]);
    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(Request $request): RedirectResponse
    {

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('subjects')->where(fn ($q) => $q->where('level', $request->level)),
            ],
            'description' => ['nullable', 'string'],
            'level' => ['required', 'string', 'in:nursery,primary,secondary'],
        ]);

        $dto = \App\DTOs\SubjectDTO::fromRequest($request);

        $this->subjectService->createSubject($dto);

        return back()->with('success', 'Subject created successfully.');

    }

    /**
     * Update the specified subject in storage.
     */
    public function update(Request $request, Subject $subject): RedirectResponse
    {

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('subjects')
                    ->where(fn ($q) => $q->where('level', $request->level))
                    ->ignore($subject->id),
            ],
            'description' => ['nullable', 'string'],
            'level' => ['required', 'string', 'in:nursery,primary,secondary'],
        ]);

        $dto = \App\DTOs\SubjectDTO::fromRequest($request);

        $this->subjectService->updateSubject($subject, $dto);

        return back()->with('success', 'Subject updated successfully.');

    }

    /**
     * Remove the specified subject from storage.
     */
    public function destroy(Subject $subject): RedirectResponse
    {

        $deleted = $this->subjectService->deleteSubject($subject);

        if ($deleted === false) {

            return back()->with('error', 'Cannot delete subject because it has associated topics.');

        }

        return back()->with('success', 'Subject deleted successfully.');

    }
}
