<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware('auth:admin,staff,student,web')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');

    // Dynamic UI API Helpers
    Route::get('/api/subjects/{subject}/topics', function (\App\Models\Subject $subject) {
        return $subject->topics()->orderBy('name')->get(['id', 'name']);
    })->name('api.topics.index');
});
Route::get('/debug-exception', function () {
    throw new \Exception('Debug Exception');
});
