<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserImportService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected UserImportService $userImportService
    ) {}

    public function index(Request $request): Response
    {
        $query = User::role(['examiner'])->with('roles');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('school_id', 'like', "%{$request->search}%");
            });
        }

        if ($request->school_id) {
            $query->where('school_id', $request->school_id);
        }

        return Inertia::render('Admin/Users/Staff', [
            'staff' => $query->latest()->paginate(10)->withQueryString(),
            'roles' => \Spatie\Permission\Models\Role::where('category', 'staff')->get(),
            'filters' => $request->only(['search', 'school_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'school_id' => ['required', 'exists:schools,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $dto = UserDTO::fromRequest($request);
        $this->userService->createUser($dto, $request->role);

        return back()->with('success', 'Account created successfully.');
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$staff->id],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$staff->id],
            'school_id' => ['required', 'exists:schools,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $dto = UserDTO::fromRequest($request);
        $this->userService->updateUser($staff, $dto);

        $staff->syncRoles([$request->role]);

        return back()->with('success', 'Personnel details updated.');
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
