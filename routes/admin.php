<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::post('/login', [AdminController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'role_or_permission:admin|manage settings|manage school setup|manage curriculum|manage users'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::prefix('school-setup')->middleware('permission:manage school setup')->group(function () {
        Route::name('classes.')->group(function () {
            Route::get('/classes', [\App\Http\Controllers\Admin\SchoolClassController::class, 'index'])->name('index');
            Route::post('/classes', [\App\Http\Controllers\Admin\SchoolClassController::class, 'store'])->name('store');
            Route::put('/classes/{schoolClass}', [\App\Http\Controllers\Admin\SchoolClassController::class, 'update'])->name('update');
            Route::delete('/classes/{schoolClass}', [\App\Http\Controllers\Admin\SchoolClassController::class, 'destroy'])->name('destroy');
        });

        Route::name('sessions.')->group(function () {
            Route::get('/sessions', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'index'])->name('index');
            Route::post('/sessions', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'store'])->name('store');
            Route::put('/sessions/{session}', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'update'])->name('update');
            Route::patch('/sessions/{session}/current', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'setCurrent'])->name('current');
            Route::delete('/sessions/{session}', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('curriculum')->middleware('permission:manage curriculum')->group(function () {
        Route::get('/subjects', [\App\Http\Controllers\Admin\SubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [\App\Http\Controllers\Admin\SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [\App\Http\Controllers\Admin\SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [\App\Http\Controllers\Admin\SubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::get('/topics', [\App\Http\Controllers\Admin\TopicController::class, 'index'])->name('topics.index');
        Route::post('/topics', [\App\Http\Controllers\Admin\TopicController::class, 'store'])->name('topics.store');
        Route::put('/topics/{topic}', [\App\Http\Controllers\Admin\TopicController::class, 'update'])->name('topics.update');
        Route::delete('/topics/{topic}', [\App\Http\Controllers\Admin\TopicController::class, 'destroy'])->name('topics.destroy');
    });

    Route::prefix('rbac')->middleware('permission:manage settings')->group(function () {
        Route::get('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/permissions', [\App\Http\Controllers\Admin\PermissionController::class, 'index'])->name('permissions.index');
    });

    Route::prefix('users')->middleware('permission:manage users')->group(function () {
        Route::get('/staff', [\App\Http\Controllers\Admin\StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [\App\Http\Controllers\Admin\StaffController::class, 'store'])->name('staff.store');
        Route::put('/staff/{staff}', [\App\Http\Controllers\Admin\StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staff}', [\App\Http\Controllers\Admin\StaffController::class, 'destroy'])->name('staff.destroy');
        Route::post('/staff/import', [\App\Http\Controllers\Admin\StaffController::class, 'import'])->name('staff.import');

        Route::get('/students', [\App\Http\Controllers\Admin\StudentController::class, 'index'])->name('students.index');
        Route::post('/students', [\App\Http\Controllers\Admin\StudentController::class, 'store'])->name('students.store');
        Route::put('/students/{student}', [\App\Http\Controllers\Admin\StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [\App\Http\Controllers\Admin\StudentController::class, 'destroy'])->name('students.destroy');
        Route::post('/students/import', [\App\Http\Controllers\Admin\StudentController::class, 'import'])->name('students.import');

        Route::get('/promotion', [\App\Http\Controllers\Admin\PromotionController::class, 'index'])->name('promotion.index')->middleware('permission:manage enrollment');
        Route::get('/promotion/students/{class}', [\App\Http\Controllers\Admin\PromotionController::class, 'students'])->name('promotion.students')->middleware('permission:manage enrollment');
        Route::post('/promotion/process', [\App\Http\Controllers\Admin\PromotionController::class, 'promote'])->name('promotion.process')->middleware('permission:manage enrollment');

        Route::get('/entrance', [\App\Http\Controllers\Admin\EntranceController::class, 'index'])->name('entrance.index');
        Route::post('/entrance', [\App\Http\Controllers\Admin\EntranceController::class, 'store'])->name('entrance.store');
        Route::post('/entrance/import', [\App\Http\Controllers\Admin\EntranceController::class, 'import'])->name('entrance.import');
        Route::post('/entrance/admit/{candidate}', [\App\Http\Controllers\Admin\EntranceController::class, 'admit'])->name('entrance.admit');

        Route::get('/teaching-loads', [\App\Http\Controllers\Admin\TeachingLoadController::class, 'index'])->name('teaching-loads.index');
        Route::post('/teaching-loads', [\App\Http\Controllers\Admin\TeachingLoadController::class, 'store'])->name('teaching-loads.store');
        Route::delete('/teaching-loads/{assignment}', [\App\Http\Controllers\Admin\TeachingLoadController::class, 'destroy'])->name('teaching-loads.destroy');
    });

    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});
