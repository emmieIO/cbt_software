<?php

use App\Http\Controllers\Student\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [StudentController::class, 'login'])->name('login');
    Route::post('/login', [StudentController::class, 'store'])->name('login.store');
});

// Primary Student Portal Protection
Route::middleware(['auth', 'can:access:student-portal'])->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');

    // Examination Interface
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('index')->middleware('permission:exam:view');
        // Note: access:student-portal is the baseline, exam:take is the functional permission for the exam itself
        Route::post('/{exam}/start', [StudentController::class, 'startExam'])->name('start')->middleware(['permission:exam:take', 'throttle:student-exam-start']);
        Route::get('/{attempt}', [StudentController::class, 'showExam'])->name('show')->middleware('permission:exam:take');
        Route::patch('/{attempt}/answer', [StudentController::class, 'saveAnswer'])->name('save-answer')->middleware(['permission:exam:take', 'throttle:student-exam-answer']);
        Route::post('/{attempt}/submit', [StudentController::class, 'submitExam'])->name('submit')->middleware(['permission:exam:take', 'throttle:student-exam-submit']);
        Route::get('/{attempt}/result', [StudentController::class, 'showResult'])->name('result')->middleware('permission:results:view');
    });

    // History & Records
    Route::get('/results', [StudentController::class, 'results'])->name('results.index')->middleware('permission:results:view');

    Route::post('/logout', [StudentController::class, 'logout'])->name('logout');
});
