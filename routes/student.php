<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', function () {
    return Inertia::render('Student/Login');
})->name('login');

Route::get('/dashboard', function () {
    return Inertia::render('Student/Dashboard');
})->name('dashboard');
