<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeachingLoadController extends Controller
{
    /**
     * Display a listing of teacher assignments.
     */
    public function index(Request $request): Response
    {
        $currentSession = AcademicSession::current()->first();

        $query = TeacherAssignment::with(['teacher', 'subject', 'schoolClass', 'prospectiveClass', 'academicSession']);

        if ($request->filled('school_id')) {
            $query->whereHas('teacher', function ($q) use ($request) {
                $q->where('school_id', $request->school_id);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        // Scope dropdowns by school if filtered
        $teacherQuery = User::role(['examiner', 'super_admin']);
        $classQuery = SchoolClass::query();
        $batchQuery = \App\Models\ProspectiveClass::where('is_active', true);

        if ($request->filled('school_id')) {
            $teacherQuery->where('school_id', $request->school_id);
            $classQuery->where('school_id', $request->school_id);
            $batchQuery->where('school_id', $request->school_id);
        }

        return Inertia::render('Admin/Users/TeachingLoads', [
            'assignments' => $query->latest()->paginate(15)->withQueryString(),
            'teachers' => $teacherQuery->get(['id', 'name', 'school_id']),
            'subjects' => Subject::with(['topics' => fn ($q) => $q->select('id', 'subject_id', 'school_class_id')])->get(['id', 'name']),
            'classes' => $classQuery->get(['id', 'name', 'school_id']),
            'batches' => $batchQuery->get(['id', 'name', 'school_id']),
            'filters' => $request->only(['user_id', 'school_class_id', 'school_id']),
            'current_session' => $currentSession,
        ]);
    }

    /**
     * Store a new assignment.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
            'prospective_class_id' => ['nullable', 'exists:prospective_classes,id'],
        ]);

        $teacher = User::findOrFail($request->user_id);

        if (! $request->school_class_id && ! $request->prospective_class_id) {
            return back()->withErrors(['school_class_id' => 'Please select a target class or prospective batch.']);
        }

        // Validate branch consistency
        if ($request->school_class_id) {
            $class = SchoolClass::findOrFail($request->school_class_id);
            if ($class->school_id !== $teacher->school_id) {
                return back()->withErrors(['school_class_id' => 'This class and teacher belong to different branches.']);
            }
        }

        if ($request->prospective_class_id) {
            $batch = \App\Models\ProspectiveClass::findOrFail($request->prospective_class_id);
            if ($batch->school_id !== $teacher->school_id) {
                return back()->withErrors(['prospective_class_id' => 'This batch and teacher belong to different branches.']);
            }
        }

        // Regular class MUST have a subject
        if ($request->school_class_id && ! $request->subject_id) {
            return back()->withErrors(['subject_id' => 'Regular class assignments must include a subject.']);
        }

        // Must have at least one (Subject for regular class, or Prospective batch for coordinator role)
        if (! $request->subject_id && ! $request->prospective_class_id) {
            return back()->withErrors(['subject_id' => 'Please select a subject or an entrance batch.']);
        }

        $currentSession = AcademicSession::current()->first();

        if (! $currentSession) {
            return back()->with('error', 'No current academic session found. Please set a session as current in Settings > Academic Sessions.');
        }

        TeacherAssignment::updateOrCreate([
            'user_id' => $request->user_id,
            'subject_id' => $request->subject_id,
            'school_class_id' => $request->school_class_id,
            'prospective_class_id' => $request->prospective_class_id,
            'academic_session_id' => $currentSession->id,
        ]);

        return back()->with('success', 'Teaching load assigned successfully.');
    }

    /**
     * Remove an assignment.
     */
    public function destroy(TeacherAssignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return back()->with('success', 'Teaching load removed.');
    }
}
