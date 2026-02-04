<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AdminController::class, 'login'])->name('login');
Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
