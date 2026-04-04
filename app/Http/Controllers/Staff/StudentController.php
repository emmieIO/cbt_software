<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\StudentVisibilityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(protected StudentVisibilityService $studentVisibilityService) {}

    /**
     * Display a listing of students assigned to the staff.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'school_class_id']);
        $data = $this->studentVisibilityService->getIndexData($request->user(), $filters);

        return Inertia::render('Staff/Students/Index', [
            'students' => $data['students'],
            'classes' => $data['classes'],
            'filters' => $filters,
        ]);
    }
}
