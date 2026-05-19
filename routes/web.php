<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserController;
use App\Models\Subject;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('questions', QuestionController::class);
    Route::get('/questions/batch/create', [ImportController::class, 'batchCreate'])->name('questions.batch.create');
    Route::get('/questions/import/template', [ImportController::class, 'downloadTemplate'])->name('questions.import.template');
    Route::post('/questions/import/preview', [ImportController::class, 'preview'])->name('questions.import.preview');
    Route::post('/questions/import/confirm', [ImportController::class, 'confirm'])->name('questions.import.confirm');
    Route::post('/questions/import/quick', [ImportController::class, 'quickStore'])->name('questions.import.quick');

    Route::get('/export', fn () => redirect()->route('exams.create'))->name('export.index');
    Route::post('/export/generate', [ExportController::class, 'generate'])->name('export.generate');

    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
    Route::get('/exams/pool', [ExamController::class, 'pool'])->name('exams.pool');
    Route::post('/exams/generate', [ExamController::class, 'generate'])->name('exams.generate');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
    Route::get('/exams/{exam}/download/questions', [ExamController::class, 'downloadQuestions'])->name('exams.download.questions');
    Route::get('/exams/{exam}/download/answer-key', [ExamController::class, 'downloadAnswerKey'])->name('exams.download.answer-key');
    Route::get('/exams/{exam}/download/marking-guide', [ExamController::class, 'downloadMarkingGuide'])->name('exams.download.marking-guide');
    Route::get('/exams/{exam}/preview/questions', [ExamController::class, 'previewQuestions'])->name('exams.preview.questions');
    Route::get('/exams/{exam}/preview/answer-key', [ExamController::class, 'previewAnswerKey'])->name('exams.preview.answer-key');
    Route::get('/exams/{exam}/preview/marking-guide', [ExamController::class, 'previewMarkingGuide'])->name('exams.preview.marking-guide');

    Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);

    Route::resource('subjects', SubjectController::class)->except(['show', 'create', 'edit']);
    Route::resource('topics', TopicController::class)->except(['show', 'create', 'edit']);

    Route::get('/api/subjects/{subject}/topics', function (Subject $subject) {
        return $subject->topics()->orderBy('name')->get(['id', 'name']);
    })->name('api.topics.index');
});
