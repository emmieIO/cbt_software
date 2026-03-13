<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProspectiveClass;
use App\Services\ProspectiveClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProspectiveClassController extends Controller
{
    public function __construct(protected ProspectiveClassService $classService) {}

    /**
     * Display a listing of the prospective classes (batches).
     */
    public function index(Request $request): Response
    {
        $query = ProspectiveClass::query();

        if ($request->branch) {
            $query->where('branch', $request->branch);
        }

        return Inertia::render('Admin/Classes/Prospective', [
            'classes' => $query->latest()->get(),
            'filters' => $request->only(['branch']),
        ]);
    }

    /**
     * Store a newly created prospective class in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('prospective_classes')->where(fn ($q) => $q->where('school_id', $request->school_id)),
            ],
            'description' => ['nullable', 'string'],
            'pass_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'school_id' => ['required', 'exists:schools,id'],
        ]);

        $this->classService->createClass($data);

        return back()->with('success', 'Prospective batch created successfully.');
    }

    /**
     * Update the specified prospective class in storage.
     */
    public function update(Request $request, ProspectiveClass $prospectiveClass): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('prospective_classes')
                    ->where(fn ($q) => $q->where('school_id', $request->school_id))
                    ->ignore($prospectiveClass->id),
            ],
            'description' => ['nullable', 'string'],
            'pass_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'school_id' => ['required', 'exists:schools,id'],
        ]);

        $this->classService->updateClass($prospectiveClass, $data);

        return back()->with('success', 'Prospective batch updated successfully.');
    }

    /**
     * Remove the specified prospective class from storage.
     */
    public function destroy(ProspectiveClass $prospectiveClass): RedirectResponse
    {
        $deleted = $this->classService->deleteClass($prospectiveClass);

        if ($deleted === false) {
            return back()->with('error', 'Cannot delete batch because it has associated candidates or exams.');
        }

        return back()->with('success', 'Prospective batch deleted successfully.');
    }
}
