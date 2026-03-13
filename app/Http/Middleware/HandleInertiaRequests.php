<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Use standard 'web' guard for all users
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['roles', 'permissions']);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    ...$user->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'roles' => $user->getRoleNames(),
                ] : null,
                'dashboard_url' => $user ? app(\App\Services\AuthService::class)->getRedirectUrl($user) : null,
                'notifications' => $user ? $user->unreadNotifications()->latest()->take(10)->get() : [],
                'is_seeding' => $user ? \Illuminate\Support\Facades\Cache::get("user_{$user->id}_seeding_status") : null,
            ],
            'academic_session' => \App\Models\AcademicSession::current()->first(),
            'branches' => \App\Models\School::where('is_active', true)
                ->when($user && ! $user->can('sys:manage_settings'), fn ($q) => $q->where('id', $user->school_id))
                ->get()
                ->mapWithKeys(fn ($s) => [
                    $s->id => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'slug' => $s->slug,
                        'type' => $s->type,
                    ],
                ]),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
