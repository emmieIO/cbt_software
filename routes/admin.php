<?php

use App\Http\Controllers\Admin\AcademicSessionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PermissionOverviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TopicController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::post('/login', [AdminController::class, 'store'])->name('login.store');
});

// Primary Admin Portal Protection
Route::middleware(['auth', 'can:access:admin-portal'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // 01. School Architecture (Academic Sessions, Classes)
    Route::prefix('school-setup')->middleware('permission:admin:manage_setup')->group(function () {
        Route::name('classes.')->group(function () {
            Route::get('/classes', [SchoolClassController::class, 'index'])->name('index');
            Route::post('/classes', [SchoolClassController::class, 'store'])->name('store');
            Route::put('/classes/{schoolClass}', [SchoolClassController::class, 'update'])->name('update');
            Route::delete('/classes/{schoolClass}', [SchoolClassController::class, 'destroy'])->name('destroy');
        });

        Route::name('sessions.')->group(function () {
            Route::get('/sessions', [AcademicSessionController::class, 'index'])->name('index');
            Route::post('/sessions', [AcademicSessionController::class, 'store'])->name('store');
            Route::put('/sessions/{session}', [AcademicSessionController::class, 'update'])->name('update');
            Route::patch('/sessions/{session}/current', [AcademicSessionController::class, 'setCurrent'])->name('current');
            Route::delete('/sessions/{session}', [AcademicSessionController::class, 'destroy'])->name('destroy');
        });
    });

    // 02. Academic Curriculum
    Route::prefix('curriculum')->middleware('permission:admin:manage_curriculum')->group(function () {
        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::get('/topics', [TopicController::class, 'index'])->name('topics.index');
        Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::put('/topics/{topic}', [TopicController::class, 'update'])->name('topics.update');
        Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
    });

    // 03. Global Governance (RBAC)
    Route::prefix('rbac')->middleware('permission:sys:manage_settings')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/overview', PermissionOverviewController::class)->name('permissions.overview');
    });

    // 04. Multi-Campus Establishment (Super Admin Only)
    Route::prefix('super-admin')->middleware('permission:sys:manage_schools')->group(function () {
        Route::get('/branches', [SchoolController::class, 'index'])->name('schools.index');
        Route::post('/branches', [SchoolController::class, 'store'])->name('schools.store');
        Route::put('/branches/{school}', [SchoolController::class, 'update'])->name('schools.update');
        Route::delete('/branches/{school}', [SchoolController::class, 'destroy'])->name('schools.destroy');
    });

    // 05. Personnel Management
    Route::prefix('users')->group(function () {
        Route::prefix('staff')->name('staff.')->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('index')->middleware('permission:staff:view');
            Route::get('/create', [StaffController::class, 'create'])->name('create')->middleware('permission:staff:create');
            Route::post('/', [StaffController::class, 'store'])->name('store')->middleware('permission:staff:create');
            Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit')->middleware('permission:staff:edit');
            Route::put('/{staff}', [StaffController::class, 'update'])->name('update')->middleware('permission:staff:edit');
            Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy')->middleware('permission:staff:delete');
            Route::post('/import', [StaffController::class, 'import'])->name('import')->middleware('permission:staff:create');
        });

        Route::prefix('students')->name('students.')->group(function () {
            Route::get('/', [StudentController::class, 'index'])->name('index')->middleware('permission:student:view');
            Route::get('/create', [StudentController::class, 'create'])->name('create')->middleware('permission:student:create');
            Route::post('/', [StudentController::class, 'store'])->name('store')->middleware('permission:student:create');
            Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('edit')->middleware('permission:student:edit');
            Route::put('/{student}', [StudentController::class, 'update'])->name('update')->middleware('permission:student:edit');
            Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy')->middleware('permission:student:delete');
            Route::post('/import', [StudentController::class, 'import'])->name('import')->middleware('permission:student:create');
        });
    });

    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});
