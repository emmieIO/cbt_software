<?php

use App\Http\Controllers\Student\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [StudentController::class, 'login'])->name('login');
    Route::post('/login', [StudentController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    Route::post('/exams/{exam}/start', [StudentController::class, 'startExam'])->name('exams.start');
    Route::get('/exams/{attempt}', [StudentController::class, 'showExam'])->name('exams.show');
    Route::post('/exams/{attempt}/submit', [StudentController::class, 'submitExam'])->name('exams.submit');
    Route::get('/exams/{attempt}/result', [StudentController::class, 'showResult'])->name('exams.result');
    Route::post('/logout', [StudentController::class, 'logout'])->name('logout');
});
