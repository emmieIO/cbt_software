<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClassLevel;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class SchoolClassController extends Controller
{
    public function __construct(protected \App\Services\SchoolClassService $classService) {}

    /**
     * Display a listing of the school classes.
     */
    public function index(Request $request): Response
    {
        $query = SchoolClass::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        return Inertia::render('Admin/Classes/Index', [
            'classes' => $query->orderBy('level')->orderBy('name')->paginate(10)->withQueryString(),
            'levels' => collect(\App\Enums\ClassLevel::cases())->map(fn ($l) => [
                'value' => $l->value,
                'label' => Str::title($l->value),
            ]),
            'filters' => $request->only(['search', 'level']),
        ]);
    }

    /**
     * Store a newly created school class in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('school_classes')->where(fn ($q) => $q->where('level', $request->level)),
            ],
            'level' => ['required', new Enum(\App\Enums\ClassLevel::class)],
        ]);

        $dto = \App\DTOs\SchoolClassDTO::fromRequest($request);
        $this->classService->createClass($dto);

        return back()->with('success', 'Global academic class created successfully.');
    }

    /**
     * Update the specified school class in storage.
     */
    public function update(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('school_classes')
                    ->where(fn ($q) => $q->where('level', $request->level))
                    ->ignore($schoolClass->id),
            ],
            'level' => ['required', new Enum(\App\Enums\ClassLevel::class)],
        ]);

        $dto = \App\DTOs\SchoolClassDTO::fromRequest($request);
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
