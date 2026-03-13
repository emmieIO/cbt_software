<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function __construct(protected \App\Services\TopicService $topicService) {}

    /**
     * Display a listing of the topics.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Base Query: Load everything since we want to handle large repositories cleanly in the UI
        $query = Topic::with(['subject', 'schoolClass'])->withCount('questions');

        // Initial Context queries
        $subjectsQuery = Subject::query();
        $classesQuery = SchoolClass::query();

        // Level-based Scoping logic
        if (! $user->can('sys:manage_settings')) {
            // Staff are strictly scoped to their assigned campus level
            $school = $user->school_id ? School::find($user->school_id) : null;
            if ($school) {
                $subjectsQuery->where('level', $school->type);
                $classesQuery->where('level', $school->type);
                
                // Restrict the topics listing to match the campus level
                $query->whereHas('subject', fn($q) => $q->where('level', $school->type));
            } else {
                // No school assigned, no access
                $query->whereRaw('1 = 0');
            }
        }

        // Apply filters if provided (mostly for Super Admin global searches)
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        return Inertia::render('Admin/Topics/Index', [
            // Return all records to support the new multi-tier dynamic frontend navigation
            'topics' => $query->orderBy('name')->get(),
            'subjects' => $subjectsQuery->orderBy('name')->get(),
            'classes' => $classesQuery->orderBy('name')->get(),
            'filters' => $request->only(['subject_id', 'school_class_id']),
        ]);
    }

    /**
     * Store a newly created topic in storage.
     */
    public function store(Request $request): RedirectResponse
    {

        $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $dto = \App\DTOs\TopicDTO::fromRequest($request);

        $this->topicService->createTopic($dto);

        return back()->with('success', 'Curriculum unit initialized successfully.');

    }

    /**
     * Update the specified topic in storage.
     */
    public function update(Request $request, Topic $topic): RedirectResponse
    {

        $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $dto = \App\DTOs\TopicDTO::fromRequest($request);

        $this->topicService->updateTopic($topic, $dto);

        return back()->with('success', 'Curriculum unit modified successfully.');

    }

    /**
     * Remove the specified topic from storage.
     */
    public function destroy(Topic $topic): RedirectResponse
    {

        $deleted = $this->topicService->deleteTopic($topic);

        if ($deleted === false) {
            return back()->with('error', 'Cannot delete knowledge unit because it has associated assessment questions.');
        }

        return back()->with('success', 'Knowledge unit purged successfully.');

    }
}
