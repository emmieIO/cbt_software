<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\SchoolDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolRequest;
use App\Models\School;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function __construct(
        protected SchoolService $schoolService
    ) {}

    /**
     * Display a listing of schools.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Schools/Index', [
            'schools' => School::withCount('users')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created school.
     */
    public function store(SchoolRequest $request): RedirectResponse
    {
        $this->schoolService->createSchool(SchoolDTO::fromRequest($request));

        return redirect()->back()->with('success', 'Branch created successfully.');
    }

    /**
     * Update the specified school.
     */
    public function update(SchoolRequest $request, School $school): RedirectResponse
    {
        $updated = $this->schoolService->updateSchool($school, SchoolDTO::fromRequest($request));

        if (! $updated) {
            return redirect()->back()->with('error', 'Failed to update branch. Please try again.');
        }

        return redirect()->back()->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove the specified school.
     */
    public function destroy(School $school): RedirectResponse
    {
        $deleted = $this->schoolService->deleteSchool($school);

        if (! $deleted) {
            return redirect()->back()->with('error', 'Cannot delete a branch that has active users.');
        }

        return redirect()->back()->with('success', 'Branch deleted successfully.');
    }
}
