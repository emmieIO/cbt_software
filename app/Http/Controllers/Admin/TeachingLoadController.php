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

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        return Inertia::render('Admin/Users/TeachingLoads', [
            'assignments' => $query->latest()->paginate(15)->withQueryString(),
            'teachers' => User::role(['staff', 'subject_lead', 'admin'])->get(['id', 'name', 'school_id']),
            'subjects' => Subject::with(['topics' => fn ($q) => $q->select('id', 'subject_id', 'school_class_id')])->get(['id', 'name']),
            'classes' => SchoolClass::all(['id', 'name']),
            'batches' => \App\Models\ProspectiveClass::where('is_active', true)->get(['id', 'name']),
            'filters' => $request->only(['user_id', 'school_class_id']),
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
            'subject_id' => ['required', 'exists:subjects,id'],
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
            'prospective_class_id' => ['nullable', 'exists:prospective_classes,id'],
        ]);

        if (! $request->school_class_id && ! $request->prospective_class_id) {
            return back()->withErrors(['school_class_id' => 'Please select a target class or prospective batch.']);
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
