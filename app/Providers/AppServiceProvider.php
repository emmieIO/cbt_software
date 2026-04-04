<?php

namespace App\Providers;

use App\Repositories\Contracts\AcademicRepositoryInterface;
use App\Repositories\Contracts\AttemptRepositoryInterface;
use App\Repositories\Contracts\ExamRepositoryInterface;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentAcademicRepository;
use App\Repositories\Eloquent\EloquentAttemptRepository;
use App\Repositories\Eloquent\EloquentExamRepository;
use App\Repositories\Eloquent\EloquentQuestionRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
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
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        $this->app->bind(
            ExamRepositoryInterface::class,
            EloquentExamRepository::class
        );

        $this->app->bind(
            QuestionRepositoryInterface::class,
            EloquentQuestionRepository::class
        );

        $this->app->bind(
            AcademicRepositoryInterface::class,
            EloquentAcademicRepository::class
        );

        $this->app->bind(
            AttemptRepositoryInterface::class,
            EloquentAttemptRepository::class
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
