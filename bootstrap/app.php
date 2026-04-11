<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Services\AuthService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            RateLimiter::for('student-exam-start', fn (Request $request) => [
                Limit::perMinute(6)->by((string) ($request->user()?->id ?? $request->ip())),
            ]);

            RateLimiter::for('student-exam-answer', fn (Request $request) => [
                Limit::perMinute(120)->by((string) ($request->user()?->id ?? $request->ip())),
            ]);

            RateLimiter::for('student-exam-submit', fn (Request $request) => [
                Limit::perMinute(6)->by((string) ($request->user()?->id ?? $request->ip())),
            ]);

            RateLimiter::for('staff-ai-generation', fn (Request $request) => [
                Limit::perMinute(6)->by((string) ($request->user()?->id ?? $request->ip())),
            ]);

            RateLimiter::for('staff-heavy-write', fn (Request $request) => [
                Limit::perMinute(15)->by((string) ($request->user()?->id ?? $request->ip())),
            ]);

            RateLimiter::for('admin-imports', fn (Request $request) => [
                Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip())),
            ]);

            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->prefix('staff')
                ->name('staff.')
                ->group(base_path('routes/staff.php'));

            Route::middleware('web')
                ->prefix('student')
                ->name('student.')
                ->group(base_path('routes/student.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin/*')) {
                return route('admin.login');
            }
            if ($request->is('staff/*')) {
                return route('staff.login');
            }
            if ($request->is('student/*')) {
                return route('student.login');
            }

            return route('home');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            return $user ? app(AuthService::class)->getRedirectUrl($user) : route('home');
        });

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
