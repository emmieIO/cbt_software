<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        $notifications = array_filter([
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
        ], fn (mixed $message): bool => is_string($message) && $message !== '');

        if ($notifications !== []) {
            Inertia::flash($notifications);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'permissions' => $user->permissions ?? [],
                    'can_create_questions' => $user->canCreateQuestions(),
                    'can_edit_questions' => $user->canEditQuestions(),
                    'can_generate_questions' => $user->canGenerateQuestions(),
                    'can_review_curriculum' => $user->canReviewCurriculum(),
                    'can_manage_knowledge_base' => $user->canManageKnowledgeBase(),
                    'can_access_knowledge_base' => $user->canAccessKnowledgeBase(),
                    'can_approve_knowledge_documents' => $user->canApproveKnowledgeDocuments(),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'preview' => $request->session()->get('preview'),
            ],
        ];
    }
}
