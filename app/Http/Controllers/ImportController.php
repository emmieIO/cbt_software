<?php

namespace App\Http\Controllers;

use App\Http\Requests\Questions\ConfirmQuestionImportRequest;
use App\Http\Requests\Questions\QuestionImportPreviewRequest;
use App\Http\Requests\Questions\QuickStoreQuestionImportRequest;
use App\Jobs\ImportQuestionsJob;
use App\Services\QuestionImportPreviewService;
use App\Services\QuestionImportTemplateService;
use App\Models\Subject;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function __construct(
        private readonly QuestionImportPreviewService $previewService,
        private readonly QuestionImportTemplateService $templateService,
    ) {}

    public function index(): Response
    {
        abort_unless(request()->user()?->canCreateQuestions(), 403);

        return Inertia::render('Questions/ImportExcel');
    }

    public function batchCreate(): Response
    {
        abort_unless(request()->user()?->canCreateQuestions(), 403);

        $subjects = Subject::query()
            ->with(['topics:id,subject_id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        return Inertia::render('Questions/BatchCreate', [
            'levels' => [
                ['value' => 'lp', 'label' => 'Lower Primary'],
                ['value' => 'hp', 'label' => 'Higher Primary'],
                ['value' => 'js', 'label' => 'Junior Secondary'],
                ['value' => 'ss', 'label' => 'Senior Secondary'],
            ],
            'subjects' => $subjects,
        ]);
    }

    public function downloadTemplate(): never
    {
        abort_unless(request()->user()?->canCreateQuestions(), 403);

        $this->templateService->downloadTemplate();
    }

    public function preview(QuestionImportPreviewRequest $request)
    {
        abort_unless($request->user()?->canCreateQuestions(), 403);

        $preview = $this->previewService->buildPreview($request->uploadedFile());

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
     * @param array<int, array<string, mixed>> $rows
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
}
