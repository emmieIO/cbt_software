<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\TopicDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TopicRequest;
use App\Models\Topic;
use App\Services\TopicService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function __construct(protected TopicService $topicService) {}

    /**
     * Display a listing of the topics.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['subject_id', 'school_class_id', 'level', 'search']);
        $indexData = $this->topicService->getIndexData($request->user(), $filters);

        return Inertia::render('Admin/Topics/Index', [
            'topics' => $indexData['topics'],
            'subjects' => $indexData['subjects'],
            'classes' => $indexData['classes'],
            'levels' => $indexData['levels'],
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created topic in storage.
     */
    public function store(TopicRequest $request): RedirectResponse
    {
        $dto = TopicDTO::fromRequest($request);

        $this->topicService->createTopic($dto);

        return back()->with('success', 'Curriculum unit initialized successfully.');
    }

    /**
     * Update the specified topic in storage.
     */
    public function update(TopicRequest $request, Topic $topic): RedirectResponse
    {
        $dto = TopicDTO::fromRequest($request);

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
