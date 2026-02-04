<?php

use App\Http\Controllers\Staff\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [StaffController::class, 'login'])->name('login');
    Route::post('/login', [StaffController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');

    Route::prefix('questions')->name('questions.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/create', [StaffController::class, 'create'])->name('create');
        Route::post('/', [StaffController::class, 'storeQuestion'])->name('store');
        Route::post('/import', [StaffController::class, 'import'])->name('import');
        Route::get('/export', [StaffController::class, 'export'])->name('export');
        Route::get('/template', [StaffController::class, 'downloadTemplate'])->name('template');
    });

    Route::post('/logout', [StaffController::class, 'logout'])->name('logout');
});
