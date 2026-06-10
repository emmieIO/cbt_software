<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exports\GenerateExportRequest;
use App\Models\Exam;
use App\Models\ExamTitle;
use App\Models\Subject;
use App\Services\ExamGenerationService;
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
            'levels' => [
                ['value' => 'lp', 'label' => 'Lower Primary'],
                ['value' => 'hp', 'label' => 'Higher Primary'],
                ['value' => 'js', 'label' => 'Junior Secondary'],
                ['value' => 'ss', 'label' => 'Senior Secondary'],
            ],
            'examTitles' => ExamTitle::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function generate(GenerateExportRequest $request)
    {
        $exam = $this->examGenerationService->generateRandom($request->payload(), $request->user()->id);

        return to_route('exams.show', $exam);
    }
}
