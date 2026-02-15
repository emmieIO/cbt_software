<?php

use App\Http\Controllers\Student\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:student')->group(function () {
    Route::get('/login', [StudentController::class, 'login'])->name('login');
    Route::post('/login', [StudentController::class, 'store'])->name('login.store');
});

Route::middleware(['auth:student', 'role:student|candidate'])->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('/exams', [StudentController::class, 'index'])->name('exams.index');
    Route::get('/results', [StudentController::class, 'results'])->name('results.index');
    Route::post('/exams/{exam}/start', [StudentController::class, 'startExam'])->name('exams.start');
    Route::get('/exams/{attempt}', [StudentController::class, 'showExam'])->name('exams.show');
    Route::patch('/exams/{attempt}/answer', [StudentController::class, 'saveAnswer'])->name('exams.save-answer');
    Route::post('/exams/{attempt}/submit', [StudentController::class, 'submitExam'])->name('exams.submit');
    Route::get('/exams/{attempt}/result', [StudentController::class, 'showResult'])->name('exams.result');
    Route::post('/logout', [StudentController::class, 'logout'])->name('logout');
});
