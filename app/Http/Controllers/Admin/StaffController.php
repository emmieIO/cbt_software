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
        $query = User::role('staff')->with('roles');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('school_id', 'like', "%{$request->search}%");
            });
        }

        return Inertia::render('Admin/Users/Staff', [
            'staff' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'school_id' => ['nullable', 'string', 'max:255', 'unique:users'],
        ]);

        $dto = UserDTO::fromRequest($request);
        $this->userService->createUser($dto, 'staff');

        return back()->with('success', 'Staff member created successfully.');
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$staff->id],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$staff->id],
            'school_id' => ['nullable', 'string', 'max:255', 'unique:users,school_id,'.$staff->id],
        ]);

        $dto = UserDTO::fromRequest($request);
        $this->userService->updateUser($staff, $dto);

        return back()->with('success', 'Staff member updated successfully.');
    }

    public function destroy(User $staff): RedirectResponse
    {
        $this->userService->deleteUser($staff);

        return back()->with('success', 'Staff member deleted successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx'],
        ]);

        $count = $this->userImportService->import($request->file('file'), 'staff');

        return back()->with('success', "$count staff members imported successfully.");
    }
}
