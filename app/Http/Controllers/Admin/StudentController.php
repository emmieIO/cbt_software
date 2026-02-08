<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
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
        $query = User::role('student')->with(['roles', 'schoolClass']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('school_id', 'like', "%{$request->search}%");
            });
        }

        if ($request->school_class_id) {
            $query->where('school_class_id', $request->school_class_id);
        }

        return Inertia::render('Admin/Users/Students', [
            'students' => $query->latest()->paginate(10)->withQueryString(),
            'classes' => SchoolClass::all(),
            'filters' => $request->only(['search', 'school_class_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'school_id' => ['nullable', 'string', 'max:255', 'unique:users'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
        ]);

        $dto = UserDTO::fromRequest($request);
        $this->userService->createUser($dto, 'student');

        return back()->with('success', 'Student created successfully.');
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$student->id],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$student->id],
            'school_id' => ['nullable', 'string', 'max:255', 'unique:users,school_id,'.$student->id],
            'school_class_id' => ['required', 'exists:school_classes,id'],
        ]);

        $dto = UserDTO::fromRequest($request);
        $this->userService->updateUser($student, $dto);

        return back()->with('success', 'Student updated successfully.');
    }

    public function destroy(User $student): RedirectResponse
    {
        $this->userService->deleteUser($student);

        return back()->with('success', 'Student deleted successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx'],
        ]);

        $count = $this->userImportService->import($request->file('file'), 'student');

        return back()->with('success', "$count students imported successfully.");
    }
}
