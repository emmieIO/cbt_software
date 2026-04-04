<?php

namespace App\Http\Middleware;

use App\Models\AcademicSession;
use App\Models\School;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $isSystemAdmin = $user ? $user->can('sys:manage_settings') : false;
        $activeBranches = School::query()
            ->where('is_active', true)
            ->when($user && ! $isSystemAdmin, fn ($q) => $q->where('id', $user->school_id))
            ->get();

        if ($user) {
            $user->loadMissing(['roles', 'permissions']);
        }

        $fallbackBranchId = $activeBranches->firstWhere('slug', 'school_hq')?->id
            ?? $activeBranches->firstWhere('slug', 'primary_vgc')?->id
            ?? $activeBranches->first()?->id;

        $effectiveSchoolId = $user?->school_id;
        if ($user && $isSystemAdmin && ! $effectiveSchoolId) {
            // Admin users without explicit branch assignment should default to School HQ context.
            $effectiveSchoolId = $fallbackBranchId;
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    ...$user->toArray(),
                    'school_id' => $effectiveSchoolId,
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'roles' => $user->getRoleNames(),
                ] : null,
                'dashboard_url' => $user ? app(AuthService::class)->getRedirectUrl($user) : null,
                'notifications' => $user ? $user->unreadNotifications()->latest()->take(10)->get() : [],
                'is_seeding' => $user ? Cache::get("user_{$user->id}_seeding_status") : null,
            ],
            'academic_session' => AcademicSession::current()->first(),
            'branches' => $activeBranches
                ->mapWithKeys(fn ($s) => [
                    $s->id => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'slug' => $s->slug,
                        'type' => $this->resolveBranchType($s),
                    ],
                ]),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Resolve branch level with safe fallbacks for legacy/incorrect seed data.
     */
    protected function resolveBranchType(School $school): string
    {
        $type = is_string($school->type) ? strtolower($school->type) : strtolower($school->type?->value ?? '');
        if (in_array($type, ['nursery', 'primary', 'secondary'], true) && $type !== 'primary') {
            return $type;
        }

        $haystack = strtolower(($school->slug ?? '').' '.$school->name);
        if (str_contains($haystack, 'nursery')) {
            return 'nursery';
        }

        if (
            str_contains($haystack, 'high_school')
            || str_contains($haystack, 'high school')
            || str_contains($haystack, 'college')
            || str_contains($haystack, 'pre_degree')
            || str_contains($haystack, 'pre-degree')
        ) {
            return 'secondary';
        }

        return 'primary';
    }
}
