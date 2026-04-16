<?php

use App\Http\Controllers\Staff\ExamController;
use App\Http\Controllers\Staff\StaffAuthController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffQuestionController;
use App\Http\Controllers\Staff\StudentController;
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
        Route::middleware("permission:bank:view")->group(function(){
            Route::get('/', [StaffQuestionController::class, 'index'])->name('index');
        });

        Route::middleware('permission:bank:use_ai')->group(function () {
            Route::get('/generate', [StaffQuestionController::class, 'generate'])->name('generate');
            Route::post('/generate', [StaffQuestionController::class, 'processGeneration'])->name('generate.process')->middleware('throttle:staff-ai-generation');
        });

        Route::middleware('permission:bank:create')->group(function () {
            Route::get('/batch', [StaffQuestionController::class, 'batchCreate'])->name('batch.create');
            Route::get('/import/setup', [StaffQuestionController::class, 'importPage'])->name('import.page');
            Route::post('/batch', [StaffQuestionController::class, 'batchStore'])->name('batch.store')->middleware('throttle:staff-heavy-write');
            Route::get('/create', [StaffQuestionController::class, 'create'])->name('create');
            Route::post('/', [StaffQuestionController::class, 'store'])->name('store');
            Route::post('/import', [StaffQuestionController::class, 'import'])->name('import')->middleware('throttle:staff-heavy-write');
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
        Route::get('/', [StudentController::class, 'index'])->name('index')->middleware('permission:student:view');
    });

    // 03. Examination Operations
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('index')->middleware('permission:exam:view');

        // Results & Prints (Moved up to avoid conflict with {exam})
        Route::middleware('permission:results:view')->group(function () {
            Route::get('/results', [ExamController::class, 'results'])->name('results');
            Route::get('/{exam}/results', [ExamController::class, 'showResults'])->name('results.show');
            Route::get('/{exam}/results-print', [ExamController::class, 'showResultsPrint'])->name('results.print');
            Route::get('/{exam}/results/{student}', [ExamController::class, 'showStudentResult'])->name('results.student');
            Route::get('/{exam}/results/{student}/print', [ExamController::class, 'showStudentResultPrint'])->name('results.student.print');
        });

        Route::middleware('permission:exam:create')->group(function () {
            Route::get('/create', [ExamController::class, 'create'])->name('create');
            Route::post('/', [ExamController::class, 'store'])->name('store');
        });

        Route::get('/{exam}', [ExamController::class, 'show'])->name('show')->middleware('permission:exam:view');

        Route::middleware('permission:exam:edit')->group(function () {
            Route::get('/{exam}/edit', [ExamController::class, 'edit'])->name('edit');
            Route::put('/{exam}', [ExamController::class, 'update'])->name('update');
            Route::put('/{exam}/status', [ExamController::class, 'updateStatus'])->name('status.update');
            Route::get('/{exam}/questions', [ExamController::class, 'manageQuestions'])->name('questions');
            Route::post('/{exam}/questions', [ExamController::class, 'updateQuestions'])->name('questions.update')->middleware('throttle:staff-heavy-write');
            Route::post('/{exam}/ai-select', [ExamController::class, 'aiSelectQuestions'])->name('questions.ai-select')->middleware('throttle:staff-heavy-write');
        });

        Route::delete('/{exam}', [ExamController::class, 'destroy'])->name('destroy')->middleware('permission:exam:delete');

        // Physical Paper Generation
        Route::get('/{exam}/print', [ExamController::class, 'showHardCopy'])->name('print')->middleware('permission:exam:view');
        Route::get('/{exam}/answer-sheet', [ExamController::class, 'showAnswerSheet'])->name('answer-sheet')->middleware('permission:exam:view');
    });

    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');
});
