<?php

use App\Http\Controllers\Staff\StaffAuthController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffQuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [StaffAuthController::class, 'login'])->name('login');
    Route::post('/login', [StaffAuthController::class, 'authenticate'])->name('login.store');
});

Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/dashboard', StaffDashboardController::class)->name('dashboard');

    Route::prefix('questions')->name('questions.')->group(function () {
        Route::get('/', [StaffQuestionController::class, 'index'])->name('index');
        Route::get('/generate', [StaffQuestionController::class, 'generate'])->name('generate');
        Route::post('/generate', [StaffQuestionController::class, 'processGeneration'])->name('generate.process');
        Route::get('/create', [StaffQuestionController::class, 'create'])->name('create');
        Route::post('/', [StaffQuestionController::class, 'store'])->name('store');
        Route::post('/import', [StaffQuestionController::class, 'import'])->name('import');
        Route::get('/export', [StaffQuestionController::class, 'export'])->name('export');
        Route::get('/template', [StaffQuestionController::class, 'downloadTemplate'])->name('template');
        Route::patch('/{question}/toggle-status', [StaffQuestionController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/bulk-delete', [StaffQuestionController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::delete('/{question}', [StaffQuestionController::class, 'destroy'])->name('destroy');
    });

    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');
});
