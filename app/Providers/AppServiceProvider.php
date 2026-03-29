<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentUserRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\ExamRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentExamRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\QuestionRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentQuestionRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\AcademicRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentAcademicRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\AttemptRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentAttemptRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Gate::before(function ($user, $ability) {
            return $user->hasPermissionTo('sys:manage_settings') ? true : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
