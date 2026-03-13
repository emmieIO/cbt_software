<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\School;
use App\Models\SchoolClass;
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
        $school = $user->school_id ? School::find($user->school_id) : null;
        
        // Base Query: Active students in the same campus
        $query = User::role('candidate')
            ->where('status', 'active')
            ->where('school_id', $user->school_id)
            ->with(['schoolClass']);

        // Classes for filter: All classes matching the school's academic level (Primary/Secondary)
        $classesQuery = SchoolClass::query();
        if ($school) {
            $classesQuery->where('level', $school->type);
        }
        $classes = $classesQuery->orderBy('name')->get();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('username', 'like', "%{$request->search}%");
            });
        }

        if ($request->school_class_id) {
            $query->where('school_class_id', $request->school_class_id);
        }

        return Inertia::render('Staff/Students/Index', [
            'students' => $query->latest()->paginate(10)->withQueryString(),
            'classes' => $classes,
            'filters' => $request->only(['search', 'school_class_id']),
        ]);
    }
}
