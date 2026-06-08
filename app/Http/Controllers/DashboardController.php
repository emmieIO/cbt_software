<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Support\RichContent;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'totalQuestions' => Question::query()->count(),
                'totalSubjects' => Subject::query()->count(),
                'totalTopics' => Topic::query()->count(),
                'totalUsers' => User::query()->count(),
                'flaggedQuestions' => Question::query()->frequentlyUsed()->count(),
            ],
            'recentQuestions' => Question::query()
                ->with(['topic.subject', 'creator'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($q) => [
                    'id' => $q->id,
                    'content' => RichContent::text($q->content),
                    'type' => $q->type->value,
                    'used_count' => $q->used_count,
                    'subject' => $q->topic?->subject?->name,
                    'created_by' => $q->creator?->name,
                ]),
        ]);
    }
}
