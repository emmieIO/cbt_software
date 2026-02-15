<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Services\AuthService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
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
            $user = null;
            foreach (['admin', 'staff', 'student', 'web'] as $guard) {
                if ($request->user($guard)) {
                    $user = $request->user($guard);
                    break;
                }
            }

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
