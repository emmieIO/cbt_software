<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exports\GenerateExportRequest;
use App\Models\AcademicSession;
use App\Models\ExamTitle;
use App\Models\Subject;
use App\Services\ExamGenerationService;
use App\Support\AcademicLevels;
use Inertia\Inertia;
use Inertia\Response;

class ExportController extends Controller
{
    public function __construct(private readonly ExamGenerationService $examGenerationService) {}

    public function index(): Response
    {
        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Export', [
            'subjects' => $subjects,
            'levels' => AcademicLevels::levelOptions(),
            'classLevels' => AcademicLevels::classOptions(),
            'examTitles' => ExamTitle::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name'),
            'academicSessions' => AcademicSession::query()
                ->where('is_active', true)
                ->orderByDesc('starts_at')
                ->get(['id', 'name']),
            'activeAcademicSessionId' => AcademicSession::query()->where('is_active', true)->value('id'),
        ]);
    }

    public function generate(GenerateExportRequest $request)
    {
        $exam = $this->examGenerationService->generateRandom($request->payload(), $request->user()->id);

        return to_route('exams.show', $exam);
    }
}
