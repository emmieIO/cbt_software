<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * Display a listing of students assigned to the staff.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get classes assigned to this staff member
        $assignedClassIds = $user->currentAssignments()
            ->pluck('school_class_id')
            ->filter()
            ->unique();

        $query = User::role('candidate')
            ->where('status', 'active')
            ->whereIn('school_class_id', $assignedClassIds)
            ->with(['schoolClass']);

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

        // Get the actual class models for the filter dropdown
        $classes = \App\Models\SchoolClass::whereIn('id', $assignedClassIds)->get();

        return Inertia::render('Staff/Students/Index', [
            'students' => $query->latest()->paginate(10)->withQueryString(),
            'classes' => $classes,
            'filters' => $request->only(['search', 'school_class_id']),
        ]);
    }
}
