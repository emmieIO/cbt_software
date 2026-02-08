<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
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
        $query = Topic::with(['subject', 'schoolClass'])->withCount('questions');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        return Inertia::render('Admin/Topics/Index', [
            'topics' => $query->latest()->paginate(10)->withQueryString(),
            'subjects' => Subject::all(),
            'classes' => SchoolClass::all(),
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

        return back()->with('success', 'Topic created successfully.');

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

        return back()->with('success', 'Topic updated successfully.');

    }

    /**
     * Remove the specified topic from storage.
     */
    public function destroy(Topic $topic): RedirectResponse
    {

        $deleted = $this->topicService->deleteTopic($topic);

        if ($deleted === false) {

            return back()->with('error', 'Cannot delete topic because it has associated questions.');

        }

        return back()->with('success', 'Topic deleted successfully.');

    }
}
