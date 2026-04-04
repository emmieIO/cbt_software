<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffImportRequest;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\User;
use App\Services\Admin\StaffManagementService;
use App\Services\UserImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(
        protected StaffManagementService $staffManagementService,
        protected UserImportService $userImportService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'school_id']);
        $data = $this->staffManagementService->getIndexData($filters);

        return Inertia::render('Admin/Users/Staff', [
            'staff' => $data['staff'],
            'branches' => $data['branches'],
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $context = $this->staffManagementService->getFormContext();

        return Inertia::render('Admin/Users/Staff/Create', [
            'branches' => $context['branches'],
            'roles' => $context['roles'],
        ]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $dto = UserDTO::fromRequest($request);
        $this->staffManagementService->createStaff(
            $dto,
            $request->string('role')->toString(),
            $request->array('school_ids'),
            $request->input('primary_school_id')
        );

        return to_route('admin.staff.index')->with('success', 'Personnel record created successfully.');
    }

    public function edit(User $staff): Response
    {
        $context = $this->staffManagementService->getFormContext();

        return Inertia::render('Admin/Users/Staff/Edit', [
            'staff' => $staff->load(['roles', 'schools']),
            'branches' => $context['branches'],
            'roles' => $context['roles'],
        ]);
    }

    public function update(StaffRequest $request, User $staff): RedirectResponse
    {
        $dto = UserDTO::fromRequest($request);
        $this->staffManagementService->updateStaff(
            $staff,
            $dto,
            $request->string('role')->toString(),
            $request->array('school_ids'),
            $request->input('primary_school_id')
        );

        return to_route('admin.staff.index')->with('success', 'Personnel details updated.');
    }

    public function destroy(User $staff): RedirectResponse
    {
        $deleted = $this->staffManagementService->deleteStaff($staff);
        if (! $deleted) {
            return back()->with('error', 'You cannot delete this user account.');
        }

        return back()->with('success', 'Personnel record removed.');
    }

    public function import(StaffImportRequest $request): RedirectResponse
    {
        $count = $this->userImportService->import($request->file('file'), 'examiner');

        return back()->with('success', "$count personnel records imported successfully.");
    }
}
