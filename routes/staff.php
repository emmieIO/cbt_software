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
    Route::get('/dashboard', StaffDashboardController::class)->name('dashboard')->middleware('role_or_permission:staff|admin');

    Route::prefix('questions')->name('questions.')->middleware('role_or_permission:staff|admin|subject_lead|view questions')->group(function () {
        Route::get('/', [StaffQuestionController::class, 'index'])->name('index');
        Route::get('/generate', [StaffQuestionController::class, 'generate'])->name('generate');
        Route::post('/generate', [StaffQuestionController::class, 'processGeneration'])->name('generate.process');
        Route::get('/create', [StaffQuestionController::class, 'create'])->name('create');
        Route::post('/', [StaffQuestionController::class, 'store'])->name('store');
        Route::get('/{question}/edit', [StaffQuestionController::class, 'edit'])->name('edit');
        Route::put('/{question}', [StaffQuestionController::class, 'update'])->name('update');
        Route::delete('/{question}', [StaffQuestionController::class, 'destroy'])->name('destroy');

        Route::post('/import', [StaffQuestionController::class, 'import'])->name('import');
        Route::get('/export', [StaffQuestionController::class, 'export'])->name('export');
        Route::get('/template', [StaffQuestionController::class, 'downloadTemplate'])->name('template');
        Route::post('/bulk-destroy', [StaffQuestionController::class, 'bulkDestroy'])->name('bulk-destroy');
    });

    Route::prefix('exams')->name('exams.')->middleware('role_or_permission:staff|admin|subject_lead')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\ExamController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Staff\ExamController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Staff\ExamController::class, 'store'])->name('store');
        Route::get('/{exam}', [\App\Http\Controllers\Staff\ExamController::class, 'show'])->name('show');
        
        // Question Management (Directly on Exam)
        Route::get('/{exam}/questions', [\App\Http\Controllers\Staff\ExamController::class, 'manageQuestions'])->name('questions');
        Route::post('/{exam}/questions', [\App\Http\Controllers\Staff\ExamController::class, 'updateQuestions'])->name('questions.update');
        Route::post('/{exam}/ai-select', [\App\Http\Controllers\Staff\ExamController::class, 'aiSelectQuestions'])->name('ai-select');

        Route::get('/{exam}/edit', [\App\Http\Controllers\Staff\ExamController::class, 'edit'])->name('edit');
        Route::put('/{exam}', [\App\Http\Controllers\Staff\ExamController::class, 'update'])->name('update');
        Route::delete('/{exam}', [\App\Http\Controllers\Staff\ExamController::class, 'destroy'])->name('destroy');
    });

    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');
});
