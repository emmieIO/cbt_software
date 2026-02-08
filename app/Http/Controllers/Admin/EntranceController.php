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

class EntranceController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected UserImportService $userImportService
    ) {}

    /**
     * List all entrance exam candidates.
     */
    public function index(Request $request): Response
    {
        $query = User::role('candidate')->with(['schoolClass']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('school_id', 'like', "%{$request->search}%");
            });
        }

        return Inertia::render('Admin/Users/Candidates', [
            'candidates' => $query->latest()->paginate(10)->withQueryString(),
            'classes' => SchoolClass::all(), // Used for targeting the entry class
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Enroll a new candidate.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'school_id' => ['required', 'string', 'max:255', 'unique:users'], // App Number
            'school_class_id' => ['nullable', 'exists:school_classes,id'], // Target Entry Class
        ]);

        $dto = UserDTO::fromRequest($request);
        $this->userService->createUser($dto, 'candidate');

        return back()->with('success', 'Candidate enrolled for entrance exam.');
    }

    /**
     * Batch enroll candidates via Excel.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx'],
        ]);

        $count = $this->userImportService->import($request->file('file'), 'candidate');

        return back()->with('success', "$count candidates imported successfully.");
    }

    /**
     * Convert a successful candidate to a full student.
     */
    public function admit(Request $request, User $candidate): RedirectResponse
    {
        $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
        ]);

        $candidate->update([
            'school_class_id' => $request->school_class_id,
            'status' => 'active',
        ]);

        $candidate->syncRoles(['student']);

        // Record enrollment for the admitted student
        $currentSession = \App\Models\AcademicSession::current()->first();
        if ($currentSession) {
            \App\Models\ClassEnrollment::updateOrCreate(
                ['user_id' => $candidate->id, 'academic_session_id' => $currentSession->id],
                ['school_class_id' => $request->school_class_id]
            );
        }

        return back()->with('success', "{$candidate->name} has been officially admitted.");
    }
}
