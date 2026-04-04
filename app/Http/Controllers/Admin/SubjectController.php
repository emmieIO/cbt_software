<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\SubjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Subject;
use App\Services\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function __construct(protected SubjectService $subjectService) {}

    /**
     * Display a listing of the subjects.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['level', 'search']);
        $data = $this->subjectService->getIndexData($filters);

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $data['subjects'],
            'counts' => $data['counts'],
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(SubjectRequest $request): RedirectResponse
    {
        $dto = SubjectDTO::fromRequest($request);

        $this->subjectService->createSubject($dto);

        return back()->with('success', 'Subject created successfully.');
    }

    /**
     * Update the specified subject in storage.
     */
    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $dto = SubjectDTO::fromRequest($request);

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
