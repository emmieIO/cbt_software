<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentImportRequest;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\User;
use App\Services\Admin\StudentManagementService;
use App\Services\UserImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(
        protected StudentManagementService $studentManagementService,
        protected UserImportService $userImportService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'school_class_id', 'school_id']);
        $indexData = $this->studentManagementService->getIndexData($filters);

        return Inertia::render('Admin/Users/Students', [
            'students' => $indexData['students'],
            'classes' => $indexData['classes'],
            'branches' => $indexData['branches'],
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $context = $this->studentManagementService->getFormContext();

        return Inertia::render('Admin/Users/Students/Create', [
            'classes' => $context['classes'],
            'branches' => $context['branches'],
            'roles' => $context['roles'],
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $dto = UserDTO::fromRequest($request);
        $this->studentManagementService->createCandidate($dto, $request->string('role')->toString());

        return to_route('admin.students.index')->with('success', 'Candidate record created successfully.');
    }

    public function edit(User $student): Response
    {
        $context = $this->studentManagementService->getFormContext();

        return Inertia::render('Admin/Users/Students/Edit', [
            'student' => $student->load('roles'),
            'classes' => $context['classes'],
            'branches' => $context['branches'],
            'roles' => $context['roles'],
        ]);
    }

    public function update(StudentRequest $request, User $student): RedirectResponse
    {
        $dto = UserDTO::fromRequest($request);
        $this->studentManagementService->updateCandidate($student, $dto, $request->string('role')->toString());

        return to_route('admin.students.index')->with('success', 'Candidate record updated successfully.');
    }

    public function destroy(User $student): RedirectResponse
    {
        $deleted = $this->studentManagementService->deleteCandidate($student);
        if (! $deleted) {
            return back()->with('error', 'You cannot delete this user account.');
        }

        return back()->with('success', 'Candidate record deleted successfully.');
    }

    public function import(StudentImportRequest $request): RedirectResponse
    {
        $count = $this->userImportService->import($request->file('file'), 'candidate');

        return back()->with('success', "$count candidates imported successfully.");
    }
}
