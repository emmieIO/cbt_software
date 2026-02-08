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

        $query = TeacherAssignment::with(['teacher', 'subject', 'schoolClass', 'academicSession']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        return Inertia::render('Admin/Users/TeachingLoads', [
            'assignments' => $query->latest()->paginate(15)->withQueryString(),
            'teachers' => User::role(['staff', 'subject_lead'])->get(['id', 'name', 'school_id']),
            'subjects' => Subject::all(['id', 'name']),
            'classes' => SchoolClass::all(['id', 'name']),
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
            'school_class_id' => ['required', 'exists:school_classes,id'],
        ]);

        $currentSession = AcademicSession::current()->firstOrFail();

        TeacherAssignment::updateOrCreate([
            'user_id' => $request->user_id,
            'subject_id' => $request->subject_id,
            'school_class_id' => $request->school_class_id,
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
