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
    public function index(): Response
    {

        return Inertia::render('Admin/Subjects/Index', [

            'subjects' => Subject::withCount('topics')->latest()->get(),

        ]);

    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(Request $request): RedirectResponse
    {

        $request->validate([

            'name' => ['required', 'string', 'max:255', 'unique:subjects,name'],

            'description' => ['nullable', 'string'],

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

            'name' => ['required', 'string', 'max:255', 'unique:subjects,name,'.$subject->id],

            'description' => ['nullable', 'string'],

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
