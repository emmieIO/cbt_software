<?php

use App\Http\Controllers\Staff\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [StaffController::class, 'login'])->name('login');
    Route::post('/login', [StaffController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [StaffController::class, 'logout'])->name('logout');
});
