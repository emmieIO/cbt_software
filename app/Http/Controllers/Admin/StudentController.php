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

class StudentController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected UserImportService $userImportService
    ) {}

    public function index(Request $request): Response
    {
        $query = User::role('candidate')
            ->where('status', 'active')
            ->with(['schoolClass', 'roles']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('username', 'like', "%{$request->search}%");
            });
        }

        if ($request->school_id) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->school_class_id) {
            $query->where('school_class_id', $request->school_class_id);
        }

        return Inertia::render('Admin/Users/Students', [
            'students' => $query->latest()->paginate(10)->withQueryString(),
            'classes' => \App\Models\SchoolClass::all(),
            'roles' => \Spatie\Permission\Models\Role::where('category', 'student')->get(),
            'filters' => $request->only(['search', 'school_class_id', 'school_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'school_id' => ['required', 'exists:schools,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $dto = UserDTO::fromRequest($request);
        $dto->status = 'active';
        $user = $this->userService->createUser($dto, $request->role);

        return back()->with('success', 'Candidate record created successfully.');
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$student->id],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$student->id],
            'school_id' => ['required', 'exists:schools,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $dto = UserDTO::fromRequest($request);
        $this->userService->updateUser($student, $dto);
        $student->syncRoles([$request->role]);

        return back()->with('success', 'Candidate record updated successfully.');
    }

    public function destroy(User $student): RedirectResponse
    {
        $this->userService->deleteUser($student);

        return back()->with('success', 'Candidate record deleted successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx'],
        ]);

        $count = $this->userImportService->import($request->file('file'), 'candidate');

        return back()->with('success', "$count candidates imported successfully.");
    }
}
