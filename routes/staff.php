<?php

use App\Http\Controllers\Staff\StaffAuthController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffQuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [StaffAuthController::class, 'login'])->name('login');
    Route::post('/login', [StaffAuthController::class, 'authenticate'])->name('login.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', StaffDashboardController::class)->name('dashboard')->middleware('role:staff');

    Route::prefix('questions')->name('questions.')->middleware('role_or_permission:staff|admin|subject_lead|view questions')->group(function () {
        Route::get('/', [StaffQuestionController::class, 'index'])->name('index');
        Route::get('/generate', [StaffQuestionController::class, 'generate'])->name('generate')->middleware('permission:use ai lab');
        Route::post('/generate', [StaffQuestionController::class, 'processGeneration'])->name('generate.process')->middleware('permission:use ai lab');
        Route::get('/create', [StaffQuestionController::class, 'create'])->name('create')->middleware('permission:create questions');
        Route::post('/', [StaffQuestionController::class, 'store'])->name('store')->middleware('permission:create questions');
        Route::get('/{question}/edit', [StaffQuestionController::class, 'edit'])->name('edit')->middleware('permission:edit questions');
        Route::put('/{question}', [StaffQuestionController::class, 'update'])->name('update')->middleware('permission:edit questions');
        Route::post('/import', [StaffQuestionController::class, 'import'])->name('import')->middleware('permission:manage question bank');
        Route::get('/export', [StaffQuestionController::class, 'export'])->name('export')->middleware('permission:export questions');
        Route::get('/template', [StaffQuestionController::class, 'downloadTemplate'])->name('template');
        Route::patch('/{question}/toggle-status', [StaffQuestionController::class, 'toggleStatus'])->name('toggle-status')->middleware('permission:edit questions');
        Route::delete('/bulk-delete', [StaffQuestionController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware('permission:manage question bank');
        Route::delete('/{question}', [StaffQuestionController::class, 'destroy'])->name('destroy')->middleware('permission:delete questions');
    });

    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');
});
