<?php

use App\Http\Controllers\Student\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [StudentController::class, 'login'])->name('login');
Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
