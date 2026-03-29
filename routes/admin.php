<?php

use App\Http\Controllers\Admin\AdminController;
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
        Route::prefix('staff')->name('staff.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\StaffController::class, 'index'])->name('index')->middleware('permission:staff:view');
            Route::get('/create', [\App\Http\Controllers\Admin\StaffController::class, 'create'])->name('create')->middleware('permission:staff:create');
            Route::post('/', [\App\Http\Controllers\Admin\StaffController::class, 'store'])->name('store')->middleware('permission:staff:create');
            Route::get('/{staff}/edit', [\App\Http\Controllers\Admin\StaffController::class, 'edit'])->name('edit')->middleware('permission:staff:edit');
            Route::put('/{staff}', [\App\Http\Controllers\Admin\StaffController::class, 'update'])->name('update')->middleware('permission:staff:edit');
            Route::delete('/{staff}', [\App\Http\Controllers\Admin\StaffController::class, 'destroy'])->name('destroy')->middleware('permission:staff:delete');
            Route::post('/import', [\App\Http\Controllers\Admin\StaffController::class, 'import'])->name('import')->middleware('permission:staff:create');
        });

        Route::prefix('students')->name('students.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\StudentController::class, 'index'])->name('index')->middleware('permission:student:view');
            Route::get('/create', [\App\Http\Controllers\Admin\StudentController::class, 'create'])->name('create')->middleware('permission:student:create');
            Route::post('/', [\App\Http\Controllers\Admin\StudentController::class, 'store'])->name('store')->middleware('permission:student:create');
            Route::get('/{student}/edit', [\App\Http\Controllers\Admin\StudentController::class, 'edit'])->name('edit')->middleware('permission:student:edit');
            Route::put('/{student}', [\App\Http\Controllers\Admin\StudentController::class, 'update'])->name('update')->middleware('permission:student:edit');
            Route::delete('/{student}', [\App\Http\Controllers\Admin\StudentController::class, 'destroy'])->name('destroy')->middleware('permission:student:delete');
            Route::post('/import', [\App\Http\Controllers\Admin\StudentController::class, 'import'])->name('import')->middleware('permission:student:create');
        });
    });

    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});
