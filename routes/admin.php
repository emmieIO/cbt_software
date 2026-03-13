<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::post('/login', [AdminController::class, 'store'])->name('login.store');
});

// Primary Admin Portal Protection: Require super_admin role
Route::middleware(['auth', 'can:admin:manage_users'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // 01. School Architecture (Academic Sessions, Classes, Batches)
    Route::prefix('school-setup')->middleware('permission:admin:manage_setup')->group(function () {
        Route::name('classes.')->group(function () {
            Route::get('/classes', [\App\Http\Controllers\Admin\SchoolClassController::class, 'index'])->name('index');
            Route::post('/classes', [\App\Http\Controllers\Admin\SchoolClassController::class, 'store'])->name('store');
            Route::put('/classes/{schoolClass}', [\App\Http\Controllers\Admin\SchoolClassController::class, 'update'])->name('update');
            Route::delete('/classes/{schoolClass}', [\App\Http\Controllers\Admin\SchoolClassController::class, 'destroy'])->name('destroy');
        });

        Route::name('prospective-classes.')->middleware('permission:admin:manage_batches')->group(function () {
            Route::get('/prospective-batches', [\App\Http\Controllers\Admin\ProspectiveClassController::class, 'index'])->name('index');
            Route::post('/prospective-batches', [\App\Http\Controllers\Admin\ProspectiveClassController::class, 'store'])->name('store');
            Route::put('/prospective-batches/{prospectiveClass}', [\App\Http\Controllers\Admin\ProspectiveClassController::class, 'update'])->name('update');
            Route::delete('/prospective-batches/{prospectiveClass}', [\App\Http\Controllers\Admin\ProspectiveClassController::class, 'destroy'])->name('destroy');
        });

        Route::name('sessions.')->group(function () {
            Route::get('/sessions', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'index'])->name('index');
            Route::post('/sessions', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'store'])->name('store');
            Route::put('/sessions/{session}', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'update'])->name('update');
            Route::patch('/sessions/{session}/current', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'setCurrent'])->name('current');
            Route::delete('/sessions/{session}', [\App\Http\Controllers\Admin\AcademicSessionController::class, 'destroy'])->name('destroy');
        });
    });

    // 02. Academic Curriculum
    Route::prefix('curriculum')->middleware('permission:admin:manage_curriculum')->group(function () {
        Route::get('/subjects', [\App\Http\Controllers\Admin\SubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [\App\Http\Controllers\Admin\SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [\App\Http\Controllers\Admin\SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [\App\Http\Controllers\Admin\SubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::get('/topics', [\App\Http\Controllers\Admin\TopicController::class, 'index'])->name('topics.index');
        Route::post('/topics', [\App\Http\Controllers\Admin\TopicController::class, 'store'])->name('topics.store');
        Route::put('/topics/{topic}', [\App\Http\Controllers\Admin\TopicController::class, 'update'])->name('topics.update');
        Route::delete('/topics/{topic}', [\App\Http\Controllers\Admin\TopicController::class, 'destroy'])->name('topics.destroy');
    });

    // 03. Global Governance (RBAC)
    Route::prefix('rbac')->middleware('permission:sys:manage_settings')->group(function () {
        Route::get('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/permissions', [\App\Http\Controllers\Admin\PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/overview', \App\Http\Controllers\Admin\PermissionOverviewController::class)->name('permissions.overview');
    });

    // 04. Multi-Campus Establishment (Super Admin Only)
    Route::prefix('super-admin')->middleware('permission:sys:manage_schools')->group(function () {
        Route::get('/branches', [\App\Http\Controllers\Admin\SchoolController::class, 'index'])->name('schools.index');
        Route::post('/branches', [\App\Http\Controllers\Admin\SchoolController::class, 'store'])->name('schools.store');
        Route::put('/branches/{school}', [\App\Http\Controllers\Admin\SchoolController::class, 'update'])->name('schools.update');
        Route::delete('/branches/{school}', [\App\Http\Controllers\Admin\SchoolController::class, 'destroy'])->name('schools.destroy');
    });

    // 05. Personnel Management
    Route::prefix('users')->group(function () {
        Route::middleware('permission:admin:manage_users')->group(function () {
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

            Route::get('/teaching-loads', [\App\Http\Controllers\Admin\TeachingLoadController::class, 'index'])->name('teaching-loads.index');
            Route::post('/teaching-loads', [\App\Http\Controllers\Admin\TeachingLoadController::class, 'store'])->name('teaching-loads.store');
            Route::delete('/teaching-loads/{assignment}', [\App\Http\Controllers\Admin\TeachingLoadController::class, 'destroy'])->name('teaching-loads.destroy');
        });

        Route::name('promotion.')->middleware('permission:admin:manage_enrollment')->group(function () {
            Route::get('/promotion', [\App\Http\Controllers\Admin\PromotionController::class, 'index'])->name('index');
            Route::get('/promotion/students/{schoolClass}', [\App\Http\Controllers\Admin\PromotionController::class, 'students'])->name('students');
            Route::post('/promotion/process', [\App\Http\Controllers\Admin\PromotionController::class, 'promote'])->name('process');
        });

        Route::middleware('permission:admin:manage_admissions')->group(function () {
            Route::get('/entrance', [\App\Http\Controllers\Admin\EntranceController::class, 'index'])->name('entrance.index');
            Route::post('/entrance', [\App\Http\Controllers\Admin\EntranceController::class, 'store'])->name('entrance.store');
            Route::put('/entrance/{candidate}', [\App\Http\Controllers\Admin\EntranceController::class, 'update'])->name('entrance.update');
            Route::post('/entrance/import', [\App\Http\Controllers\Admin\EntranceController::class, 'import'])->name('entrance.import');
            Route::post('/entrance/admit/{candidate}', [\App\Http\Controllers\Admin\EntranceController::class, 'admit'])->name('entrance.admit');
        });
    });

    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});
