<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::post('/login', [AdminController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'role_or_permission:admin|manage settings|manage question bank'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::prefix('school-setup')->name('classes.')->middleware('permission:manage settings')->group(function () {
        Route::get('/classes', [\App\Http\Controllers\Admin\SchoolClassController::class, 'index'])->name('index');
        Route::post('/classes', [\App\Http\Controllers\Admin\SchoolClassController::class, 'store'])->name('store');
        Route::put('/classes/{schoolClass}', [\App\Http\Controllers\Admin\SchoolClassController::class, 'update'])->name('update');
        Route::delete('/classes/{schoolClass}', [\App\Http\Controllers\Admin\SchoolClassController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('curriculum')->middleware('permission:manage question bank')->group(function () {
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

    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});
