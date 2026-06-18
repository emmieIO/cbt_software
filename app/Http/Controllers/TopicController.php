<?php

namespace App\Http\Controllers;

use App\Http\Requests\Topics\SaveTopicRequest;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\TopicService;
use App\Support\AcademicLevels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function __construct(private readonly TopicService $topicService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['level', 'class_level']);

        $topics = Topic::query()
            ->with('subject')
            ->withCount('questions')
            ->when($filters['level'] ?? null, fn ($query, $level) => $query->whereHas('subject', fn ($subjectQuery) => $subjectQuery->where('level', $level)))
            ->when($filters['class_level'] ?? null, fn ($query, $classLevel) => $query->where('class_level', $classLevel))
            ->orderBy('name')
            ->paginate(20);

        $subjects = Subject::query()->orderBy('name')->get(['id', 'name', 'level']);

        return Inertia::render('Topics/Index', [
            'topics' => $topics,
            'subjects' => $subjects,
            'filters' => $filters,
            'levels' => AcademicLevels::levelOptions(),
            'classLevels' => AcademicLevels::classOptions(),
        ]);
    }

    public function store(SaveTopicRequest $request): RedirectResponse
    {
        $this->topicService->create($request->payload());

        return back()->with('success', 'Topic created successfully.');
    }

    public function update(SaveTopicRequest $request, Topic $topic): RedirectResponse
    {
        $this->topicService->update($topic, $request->payload());

        return back()->with('success', 'Topic updated successfully.');
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        if ($topic->questions()->exists()) {
            return back()->with('error', 'Cannot delete topic with existing questions.');
        }

        $topic->delete();

        return back()->with('success', 'Topic deleted successfully.');
    }
}
