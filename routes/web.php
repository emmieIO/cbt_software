<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');

    // Dynamic UI API Helpers
    Route::get('/api/subjects/{subject}/topics', function (Subject $subject) {
        $query = $subject->topics()->orderBy('name');
        $schoolClassId = request()->query('school_class_id');

        if (is_string($schoolClassId) && $schoolClassId !== '') {
            $schoolClass = SchoolClass::query()->find($schoolClassId);

            if ($schoolClass) {
                $query->where('school_class_id', $schoolClass->id);
            }
        }

        return $query->get(['id', 'name', 'school_class_id']);
    })->name('api.topics.index');
});
