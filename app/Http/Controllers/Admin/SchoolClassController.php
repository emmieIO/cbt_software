<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\SchoolClassDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolClassRequest;
use App\Models\SchoolClass;
use App\Services\SchoolClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolClassController extends Controller
{
    public function __construct(protected SchoolClassService $classService) {}

    /**
     * Display a listing of the school classes.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'level']);
        $data = $this->classService->getIndexData($filters);

        return Inertia::render('Admin/Classes/Index', [
            'classes' => $data['classes'],
            'levels' => $data['levels'],
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created school class in storage.
     */
    public function store(SchoolClassRequest $request): RedirectResponse
    {
        $dto = SchoolClassDTO::fromRequest($request);
        $this->classService->createClass($dto);

        return back()->with('success', 'Global academic class created successfully.');
    }

    /**
     * Update the specified school class in storage.
     */
    public function update(SchoolClassRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $dto = SchoolClassDTO::fromRequest($request);
        $this->classService->updateClass($schoolClass, $dto);

        return back()->with('success', 'Academic class updated successfully.');
    }

    /**
     * Remove the specified school class from storage.
     */
    public function destroy(SchoolClass $schoolClass): RedirectResponse
    {
        $deleted = $this->classService->deleteClass($schoolClass);

        if ($deleted === false) {
            return back()->with('error', 'Cannot delete class because it has associated records.');
        }

        return back()->with('success', 'Academic class deleted successfully.');
    }
}
