<?php

use App\Http\Controllers\Staff\StaffAuthController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffQuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [StaffAuthController::class, 'login'])->name('login');
    Route::post('/login', [StaffAuthController::class, 'store'])->name('login.store');
});

// Primary Staff Portal Protection
Route::middleware(['auth', 'can:access:staff-portal'])->group(function () {
    Route::get('/dashboard', StaffDashboardController::class)->name('dashboard');

    // 01. Question Repository
    Route::prefix('questions')->name('questions.')->group(function () {
        Route::get('/', [StaffQuestionController::class, 'index'])->name('index');

        Route::middleware('permission:bank:use_ai')->group(function () {
            Route::get('/generate', [StaffQuestionController::class, 'generate'])->name('generate');
            Route::post('/generate', [StaffQuestionController::class, 'processGeneration'])->name('generate.process');
        });

        Route::middleware('permission:bank:create')->group(function () {
            Route::get('/batch', [StaffQuestionController::class, 'batchCreate'])->name('batch.create');
            Route::post('/batch', [StaffQuestionController::class, 'batchStore'])->name('batch.store');
            Route::get('/create', [StaffQuestionController::class, 'create'])->name('create');
            Route::post('/', [StaffQuestionController::class, 'store'])->name('store');
            Route::post('/import', [StaffQuestionController::class, 'import'])->name('import');
            Route::get('/template', [StaffQuestionController::class, 'downloadTemplate'])->name('template');
        });

        Route::middleware('permission:bank:edit')->group(function () {
            Route::get('/{question}/edit', [StaffQuestionController::class, 'edit'])->name('edit');
            Route::put('/{question}', [StaffQuestionController::class, 'update'])->name('update');
        });

        Route::middleware('permission:bank:delete')->group(function () {
            Route::delete('/{question}', [StaffQuestionController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [StaffQuestionController::class, 'bulkDestroy'])->name('bulk-destroy');
        });

        Route::get('/export', [StaffQuestionController::class, 'export'])->name('export')->middleware('permission:bank:export');
    });

    // 02. Student Visibility
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\StudentController::class, 'index'])->name('index')->middleware('permission:student:view');
    });

    // 03. Examination Operations
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\ExamController::class, 'index'])->name('index')->middleware('permission:exam:view');

        // Results & Prints (Moved up to avoid conflict with {exam})
        Route::middleware('permission:results:view')->group(function () {
            Route::get('/results', [\App\Http\Controllers\Staff\ExamController::class, 'results'])->name('results');
            Route::get('/{exam}/results', [\App\Http\Controllers\Staff\ExamController::class, 'showResults'])->name('results.show');
            Route::get('/{exam}/results-print', [\App\Http\Controllers\Staff\ExamController::class, 'showResultsPrint'])->name('results.print');
            Route::get('/{exam}/results/{student}', [\App\Http\Controllers\Staff\ExamController::class, 'showStudentResult'])->name('results.student');
            Route::get('/{exam}/results/{student}/print', [\App\Http\Controllers\Staff\ExamController::class, 'showStudentResultPrint'])->name('results.student.print');
        });

        Route::middleware('permission:exam:create')->group(function () {
            Route::get('/create', [\App\Http\Controllers\Staff\ExamController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Staff\ExamController::class, 'store'])->name('store');
        });

        Route::get('/{exam}', [\App\Http\Controllers\Staff\ExamController::class, 'show'])->name('show')->middleware('permission:exam:view');

        Route::middleware('permission:exam:edit')->group(function () {
            Route::get('/{exam}/edit', [\App\Http\Controllers\Staff\ExamController::class, 'edit'])->name('edit');
            Route::put('/{exam}', [\App\Http\Controllers\Staff\ExamController::class, 'update'])->name('update');
            Route::get('/{exam}/questions', [\App\Http\Controllers\Staff\ExamController::class, 'manageQuestions'])->name('questions');
            Route::post('/{exam}/questions', [\App\Http\Controllers\Staff\ExamController::class, 'updateQuestions'])->name('questions.update');
            Route::post('/{exam}/ai-select', [\App\Http\Controllers\Staff\ExamController::class, 'aiSelectQuestions'])->name('questions.ai-select');
        });

        Route::delete('/{exam}', [\App\Http\Controllers\Staff\ExamController::class, 'destroy'])->name('destroy')->middleware('permission:exam:delete');

        // Physical Paper Generation
        Route::get('/{exam}/print', [\App\Http\Controllers\Staff\ExamController::class, 'showHardCopy'])->name('print')->middleware('permission:exam:view');
        Route::get('/{exam}/answer-sheet', [\App\Http\Controllers\Staff\ExamController::class, 'showAnswerSheet'])->name('answer-sheet')->middleware('permission:exam:view');
    });

    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');
});
