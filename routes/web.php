<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamTitleController;
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

    Route::get('/questions/batch/create', [ImportController::class, 'batchCreate'])->name('questions.batch.create');
    Route::get('/questions/import/pdf', [ImportController::class, 'importPdf'])->name('questions.import.pdf');
    Route::get('/questions/import', [ImportController::class, 'index'])->name('questions.import.index');
    Route::get('/questions/import/template', [ImportController::class, 'downloadTemplate'])->name('questions.import.template');
    Route::get('/questions/import/pdf-template', [ImportController::class, 'downloadPdfTemplate'])->name('questions.import.pdf-template');
    Route::post('/questions/import/preview', [ImportController::class, 'preview'])->name('questions.import.preview');
    Route::post('/questions/import/confirm', [ImportController::class, 'confirm'])->name('questions.import.confirm');
    Route::post('/questions/import/quick', [ImportController::class, 'quickStore'])->name('questions.import.quick');
    Route::post('/questions/bulk-store', [QuestionController::class, 'bulkStore'])->name('questions.bulk-store');
    Route::resource('questions', QuestionController::class);

    Route::get('/export', fn () => redirect()->route('exams.create'))->name('export.index');
    Route::post('/export/generate', [ExportController::class, 'generate'])->name('export.generate');

    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::resource('exam-titles', ExamTitleController::class)->except(['show', 'create', 'edit']);
    Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
    Route::get('/exams/pool', [ExamController::class, 'pool'])->name('exams.pool');
    Route::post('/exams/generate', [ExamController::class, 'generate'])->name('exams.generate');
    Route::get('/exams/{exam}/edit-questions', [ExamController::class, 'editQuestions'])->name('exams.edit-questions');
    Route::post('/exams/{exam}/questions', [ExamController::class, 'updateQuestions'])->name('exams.update-questions');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
    Route::get('/exams/{exam}/download/questions', [ExamController::class, 'downloadQuestions'])->name('exams.download.questions');
    Route::get('/exams/{exam}/download/answer-sheet', [ExamController::class, 'downloadAnswerSheet'])->name('exams.download.answer-sheet');
    Route::get('/exams/{exam}/download/answer-key', [ExamController::class, 'downloadAnswerKey'])->name('exams.download.answer-key');
    Route::get('/exams/{exam}/download/marking-guide', [ExamController::class, 'downloadMarkingGuide'])->name('exams.download.marking-guide');
    Route::get('/exams/{exam}/preview/questions', [ExamController::class, 'previewQuestions'])->name('exams.preview.questions');
    Route::get('/exams/{exam}/preview-html/questions', [ExamController::class, 'previewQuestionsHtml'])->name('exams.preview-html.questions');
    Route::get('/exams/{exam}/preview/answer-sheet', [ExamController::class, 'previewAnswerSheet'])->name('exams.preview.answer-sheet');
    Route::get('/exams/{exam}/preview-html/answer-sheet', [ExamController::class, 'previewAnswerSheetHtml'])->name('exams.preview-html.answer-sheet');
    Route::get('/exams/{exam}/preview/answer-key', [ExamController::class, 'previewAnswerKey'])->name('exams.preview.answer-key');
    Route::get('/exams/{exam}/preview-html/answer-key', [ExamController::class, 'previewAnswerKeyHtml'])->name('exams.preview-html.answer-key');
    Route::get('/exams/{exam}/preview/marking-guide', [ExamController::class, 'previewMarkingGuide'])->name('exams.preview.marking-guide');
    Route::get('/exams/{exam}/preview-html/marking-guide', [ExamController::class, 'previewMarkingGuideHtml'])->name('exams.preview-html.marking-guide');

    Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);

    Route::resource('subjects', SubjectController::class)->except(['show', 'create', 'edit']);
    Route::resource('topics', TopicController::class)->except(['show', 'create', 'edit']);

    Route::get('/api/subjects/{subject}/topics', function (Subject $subject) {
        return $subject->topics()->orderBy('name')->get(['id', 'name']);
    })->name('api.topics.index');
});
