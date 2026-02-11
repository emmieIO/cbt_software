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
    public function index(): Response
    {
        return Inertia::render('Admin/Classes/Prospective', [
            'classes' => ProspectiveClass::latest()->get(),
        ]);
    }

    /**
     * Store a newly created prospective class in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:prospective_classes,name'],
            'description' => ['nullable', 'string'],
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
            'name' => ['required', 'string', 'max:255', 'unique:prospective_classes,name,'.$prospectiveClass->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
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
