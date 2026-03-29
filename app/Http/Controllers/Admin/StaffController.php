<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserImportService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected UserService $userService,
        protected UserImportService $userImportService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Users/Staff', [
            'staff' => $this->userRepo->getPaginatedStaff(10, $request->only(['search', 'school_id'])),
            'branches' => School::query()->where('is_active', true)->get(),
            'filters' => $request->only(['search', 'school_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Staff/Create', [
            'branches' => School::query()->where('is_active', true)->get(),
            'roles' => Role::query()->where('category', 'staff')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'school_ids' => ['required', 'array', 'min:1'],
            'school_ids.*' => ['exists:schools,id'],
            'primary_school_id' => ['nullable', 'exists:schools,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $dto = UserDTO::fromRequest($request);
        $dto->status = 'active';
        $user = $this->userService->createUser($dto, $request->role);

        $this->userRepo->syncSchools(
            $user->id,
            $request->school_ids,
            $request->primary_school_id ?? $request->school_ids[0]
        );

        return to_route('admin.staff.index')->with('success', 'Personnel record created successfully.');
    }

    public function edit(User $staff): Response
    {
        return Inertia::render('Admin/Users/Staff/Edit', [
            'staff' => $staff->load(['roles', 'schools']),
            'branches' => School::query()->where('is_active', true)->get(),
            'roles' => Role::query()->where('category', 'staff')->get(),
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$staff->id],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$staff->id],
            'school_ids' => ['required', 'array', 'min:1'],
            'school_ids.*' => ['exists:schools,id'],
            'primary_school_id' => ['nullable', 'exists:schools,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $dto = UserDTO::fromRequest($request);
        $this->userService->updateUser($staff, $dto);
        $staff->syncRoles([$request->role]);

        $this->userRepo->syncSchools(
            $staff->id,
            $request->school_ids,
            $request->primary_school_id ?? $request->school_ids[0]
        );

        return to_route('admin.staff.index')->with('success', 'Personnel details updated.');
    }

    public function destroy(User $staff): RedirectResponse
    {
        $this->userService->deleteUser($staff);

        return back()->with('success', 'Personnel record removed.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx'],
        ]);

        $count = $this->userImportService->import($request->file('file'), 'examiner');

        return back()->with('success', "$count personnel records imported successfully.");
    }
}
