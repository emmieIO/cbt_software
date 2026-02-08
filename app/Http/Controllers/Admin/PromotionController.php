<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function __construct(protected PromotionService $promotionService) {}

    /**
     * Display the promotion control center.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Promotion', [
            'classes' => $this->promotionService->getClassStatusSummary(),
        ]);
    }

    /**
     * Get students for a specific class.
     */
    public function students(SchoolClass $class): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            User::where('school_class_id', $class->id)
                ->where('status', 'active')
                ->select('id', 'name', 'school_id')
                ->get()
        );
    }

    /**
     * Handle batch promotion or graduation.
     */
    public function promote(Request $request): RedirectResponse
    {
        $request->validate([
            'from_class_id' => ['required', 'exists:school_classes,id'],
            'to_class_id' => ['nullable', 'exists:school_classes,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:users,id'],
        ]);

        $fromClass = SchoolClass::findOrFail($request->from_class_id);
        $toClass = $request->to_class_id ? SchoolClass::findOrFail($request->to_class_id) : null;

        $count = $this->promotionService->promote($fromClass, $toClass, $request->student_ids);

        $message = $toClass
            ? "$count students promoted from {$fromClass->name} to {$toClass->name}."
            : "$count students from {$fromClass->name} have been graduated.";

        return back()->with('success', $message);
    }
}
