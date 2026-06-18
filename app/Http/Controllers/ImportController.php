<?php

namespace App\Http\Controllers;

use App\Http\Requests\Questions\ConfirmQuestionImportRequest;
use App\Http\Requests\Questions\QuestionImportPreviewRequest;
use App\Http\Requests\Questions\QuickStoreQuestionImportRequest;
use App\Jobs\ImportQuestionsJob;
use App\Models\Subject;
use App\Services\PdfImportService;
use App\Services\QuestionImportPreviewService;
use App\Services\QuestionImportTemplateService;
use App\Support\AcademicLevels;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ImportController extends Controller
{
    public function __construct(
        private readonly QuestionImportPreviewService $previewService,
        private readonly QuestionImportTemplateService $templateService,
        private readonly PdfImportService $pdfImportService,
    ) {}

    public function index(): Response
    {
        abort_unless(request()->user()?->canCreateQuestions(), 403);

        return Inertia::render('Questions/ImportExcel');
    }

    public function importPdf(): Response
    {
        abort_unless(request()->user()?->canCreateQuestions(), 403);

        return Inertia::render('Questions/ImportPdf');
    }

    public function batchCreate(): Response
    {
        abort_unless(request()->user()?->canCreateQuestions(), 403);

        $subjects = Subject::query()
            ->with(['topics:id,subject_id,class_level,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        return Inertia::render('Questions/BatchCreate', [
            'levels' => AcademicLevels::levelOptions(),
            'classLevels' => AcademicLevels::classOptions(),
            'subjects' => $subjects,
        ]);
    }

    public function downloadTemplate(): never
    {
        abort_unless(request()->user()?->canCreateQuestions(), 403);

        $this->templateService->downloadTemplate();
    }

    public function downloadPdfTemplate(): BinaryFileResponse
    {
        abort_unless(request()->user()?->canCreateQuestions(), 403);

        $path = public_path('templates/question-import-pdf-template.pdf');
        abort_unless(is_file($path), 404);

        return response()->download($path, 'question-import-pdf-template.pdf');
    }

    public function preview(QuestionImportPreviewRequest $request)
    {
        abort_unless($request->user()?->canCreateQuestions(), 403);

        try {
            $file = $request->uploadedFile();
            $preview = strtolower($file->getClientOriginalExtension()) === 'pdf'
                ? $this->pdfImportService->buildPreview($file)
                : $this->previewService->buildPreview($file);
        } catch (Throwable $e) {
            Log::warning('Question import preview failed.', [
                'message' => $e->getMessage(),
                'file' => $request->file('file')?->getClientOriginalName(),
                'user_id' => $request->user()?->id,
            ]);

            return back()->with('error', 'Preview failed: '.$this->previewErrorMessage($e));
        }

        session(['import_preview' => [
            'rows' => $preview['rows'],
            'new_subjects' => $preview['new_subjects'],
            'new_topics' => $preview['new_topics'],
        ]]);

        return back()->with('preview', $preview);
    }

    public function confirm(ConfirmQuestionImportRequest $request)
    {
        abort_unless($request->user()?->canCreateQuestions(), 403);

        $preview = session('import_preview');
        if (! $preview) {
            return back()->with('error', 'No preview data found. Please upload the file again.');
        }

        $rows = $preview['rows'];
        $hasErrors = count(array_filter($rows, fn ($r) => ! $r['valid'])) > 0;
        if ($hasErrors) {
            return back()->with('error', 'Cannot import — some rows have errors. Fix and re-upload.');
        }

        return $this->handleImport($rows, $request);
    }

    public function quickStore(QuickStoreQuestionImportRequest $request)
    {
        abort_unless($request->user()?->canCreateQuestions(), 403);

        return $this->handleImport($request->rows(), $request);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function handleImport(array $rows, Request $request)
    {
        $level = strtolower(trim($request->input('level', 'js')));
        if (! in_array($level, ['lp', 'hp', 'js', 'ss'])) {
            $level = 'js';
        }

        try {
            ImportQuestionsJob::dispatch($rows, $level, $request->user()->id);
            session()->forget('import_preview');

            return to_route('questions.index')->with('success', count($rows).' questions queued for import.');
        } catch (Exception $e) {
            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    private function previewErrorMessage(Throwable $e): string
    {
        if (str_contains($e->getMessage(), 'Incorrect API key')) {
            return 'the AI provider rejected the API key. Check DEEPSEEK_API_KEY and run php artisan config:clear.';
        }

        if (str_contains($e->getMessage(), 'The required binary was not found')) {
            return 'the pdftotext binary is missing. Install poppler-utils on the server.';
        }

        return $e->getMessage() ?: 'Unable to extract questions from this file.';
    }
}
