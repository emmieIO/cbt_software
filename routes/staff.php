<?php

use App\Http\Controllers\Staff\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [StaffController::class, 'login'])->name('login');
Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
