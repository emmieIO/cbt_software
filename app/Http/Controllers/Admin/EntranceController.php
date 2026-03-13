<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Models\ProspectiveClass;
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
        $query = User::role('candidate')
            ->where('status', 'candidate')
            ->with(['schoolClass', 'prospectiveClass', 'latestAttempt' => function ($q) {
                $q->with('exam', function ($eq) {
                    $eq->withCount('questions');
                });
            }]);

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

        return Inertia::render('Admin/Users/Candidates', [
            'candidates' => $query->latest()->paginate(10)->withQueryString(),
            'classes' => SchoolClass::all(),
            'batches' => ProspectiveClass::all(),
            'filters' => $request->only(['search', 'school_id']),
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
            'username' => ['nullable', 'string', 'max:255', 'unique:users'], // Application ID
            'school_class_id' => ['required', 'exists:school_classes,id'], // Target Admission Class
            'prospective_class_id' => ['required', 'exists:prospective_classes,id'], // Exam Batch
            'school_id' => ['required', 'exists:schools,id'],
        ]);

        $dto = UserDTO::fromRequest($request);
        // Explicitly set school_id to the provided username (Application ID)
        $dto->school_id = $request->username;

        $user = $this->userService->createUser($dto, 'candidate');

        $user->update([
            'prospective_class_id' => $request->prospective_class_id,
            'status' => 'candidate',
        ]);

        return back()->with('success', 'Candidate enrolled for entrance exam.');
    }

    /**
     * Update candidate details and batch assignment.
     */
    public function update(\App\Http\Requests\Admin\UpdateCandidateRequest $request, User $candidate): RedirectResponse
    {
        $dto = UserDTO::fromRequest($request);
        // Explicitly set school_id to the provided username (Application ID)
        $dto->school_id = $request->username;

        $this->userService->updateUser($candidate, $dto);

        // Update prospective_class_id specifically
        $candidate->update(['prospective_class_id' => $request->prospective_class_id]);

        return back()->with('success', 'Candidate batch assignment updated.');
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

        $candidate->syncRoles(['candidate']); // Keep candidate role but status is active

        // Record enrollment for the admitted student
        $currentSession = \App\Models\AcademicSession::current()->first();
        if ($currentSession) {
            \App\Models\ClassEnrollment::updateOrCreate(
                ['user_id' => $candidate->id, 'academic_session_id' => $currentSession->id],
                ['school_class_id' => $request->school_class_id]
            );
        } else {
            return back()->with('error', 'Student admitted, but enrollment history could not be recorded because no current academic session is active. Please set a session as current.');
        }

        return back()->with('success', "{$candidate->name} has been officially admitted.");
    }
}
