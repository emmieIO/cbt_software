<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exams\ExamPoolRequest;
use App\Http\Requests\Exams\SaveExamSelectionRequest;
use App\Models\Exam;
use App\Models\Subject;
use App\Services\ExamGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(private readonly ExamGenerationService $examGenerationService) {}

    public function index(): Response
    {
        $exams = Exam::query()
            ->with(['creator', 'questions.topic'])
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn ($e) => (object) [
                'id' => $e->id,
                'title' => $e->title,
                'subject_name' => $e->subject_name,
                'level' => strtoupper($e->level instanceof \App\Enums\QuestionLevel ? $e->level->value : $e->level),
                'total_marks' => $e->total_marks,
                'questions_count' => $e->questions_count,
                'created_at' => $e->created_at->format('M j, Y'),
                'creator' => ['name' => $e->creator?->name],
                'topics' => $e->questions
                    ->map(fn ($question) => $question->topic?->name)
                    ->filter()
                    ->unique()
                    ->values(),
            ]);

        return Inertia::render('Exam/Index', [
            'exams' => $exams,
        ]);
    }

    public function create(): Response
    {
        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Exam/Create', [
            'subjects' => $subjects,
            'levels' => $this->levelOptions(),
        ]);
    }

    public function pool(ExamPoolRequest $request): JsonResponse
    {
        return response()->json($this->examGenerationService->pool(
            (string) $request->input('subject_id'),
            (string) $request->input('level'),
        ));
    }

    public function generate(SaveExamSelectionRequest $request)
    {
        $exam = $this->examGenerationService->generateFromSelection($request->payload(), $request->user()->id);
        $mcqs = $exam->mcqs;
        $theory = $exam->theoryQuestions;
        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Exam/Create', [
            'subjects' => $subjects,
            'levels' => $this->levelOptions(),
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->subject_name,
                'level' => strtoupper($exam->level instanceof \App\Enums\QuestionLevel ? $exam->level->value : $exam->level),
                'date' => $exam->created_at->format('F j, Y'),
                'instructions' => $exam->instructions,
                'mcq_count' => $mcqs->count(),
                'theory_count' => $theory->count(),
                'totalMarks' => $exam->total_marks,
                'mcqs' => $mcqs->values()->map(fn ($q) => [
                    'id' => $q->id,
                    'content' => $q->content,
                    'options' => $q->options->map(fn ($o) => [
                        'id' => $o->id,
                        'content' => $o->content,
                        'is_correct' => $o->is_correct,
                    ]),
                ]),
                'theory' => $theory->values()->map(fn ($q) => [
                    'id' => $q->id,
                    'content' => $q->content,
                    'marking_scheme' => $q->marking_scheme,
                ]),
            ],
        ]);
    }

    public function show(Exam $exam)
    {
        $exam->load(['mcqs.options', 'theoryQuestions']);
        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Exam/Create', [
            'subjects' => $subjects,
            'levels' => $this->levelOptions(),
            'exam' => $this->examPayload($exam),
        ]);
    }

    public function editQuestions(Exam $exam): Response
    {
        $exam->load(['questions.topic.subject', 'mcqs.options', 'theoryQuestions']);
        $subjects = Subject::query()->with('topics')->orderBy('name')->get();
        $selectedSubjectId = optional($exam->questions->first()?->topic)->subject_id;
        $level = $exam->level instanceof \App\Enums\QuestionLevel ? $exam->level->value : $exam->level;

        return Inertia::render('Exam/Create', [
            'subjects' => $subjects,
            'levels' => $this->levelOptions(),
            'exam' => [
                ...$this->examPayload($exam),
                'editable' => true,
                'subject_id' => $selectedSubjectId,
                'selected_question_ids' => $exam->questions->pluck('id')->values(),
            ],
            'initialForm' => [
                'title' => $exam->title,
                'subject_id' => $selectedSubjectId,
                'level' => $level,
                'instructions' => $exam->instructions,
            ],
        ]);
    }

    public function updateQuestions(SaveExamSelectionRequest $request, Exam $exam): RedirectResponse
    {
        $this->examGenerationService->updateSelection($exam, $request->payload());

        return to_route('exams.show', $exam)->with('success', 'Exam questions updated successfully.');
    }

    public function downloadQuestions(Exam $exam)
    {
        $exam->load(['mcqs.options', 'theoryQuestions']);
        $pdf = Pdf::loadView('pdf.exam-questions', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->download('questions_'.str($exam->title)->slug().'.pdf');
    }

    public function downloadAnswerKey(Exam $exam)
    {
        $exam->load(['mcqs.options']);
        $pdf = Pdf::loadView('pdf.exam-answer-key', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->download('answer_key_'.str($exam->title)->slug().'.pdf');
    }

    public function downloadAnswerSheet(Exam $exam)
    {
        $exam->load(['mcqs.options']);
        $pdf = Pdf::loadView('pdf.exam-answer-sheet', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->download('answer_sheet_'.str($exam->title)->slug().'.pdf');
    }

    public function downloadMarkingGuide(Exam $exam)
    {
        $exam->load(['theoryQuestions']);
        $pdf = Pdf::loadView('pdf.exam-marking-guide', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->download('marking_guide_'.str($exam->title)->slug().'.pdf');
    }

    public function previewQuestions(Exam $exam)
    {
        $exam->load(['mcqs.options', 'theoryQuestions']);
        $pdf = Pdf::loadView('pdf.exam-questions', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('questions_'.str($exam->title)->slug().'.pdf');
    }

    public function previewQuestionsHtml(Exam $exam): View
    {
        $exam->load(['mcqs.options', 'theoryQuestions']);

        return view('staff.exams.print', [
            ...$this->pdfData($exam),
            'examId' => $exam->id,
        ]);
    }

    public function previewAnswerKey(Exam $exam)
    {
        $exam->load(['mcqs.options']);
        $pdf = Pdf::loadView('pdf.exam-answer-key', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('answer_key_'.str($exam->title)->slug().'.pdf');
    }

    public function previewAnswerKeyHtml(Exam $exam): View
    {
        $exam->load(['mcqs.options']);

        return view('pdf.exam-answer-key', $this->pdfData($exam));
    }

    public function previewAnswerSheet(Exam $exam)
    {
        $exam->load(['mcqs.options']);
        $pdf = Pdf::loadView('pdf.exam-answer-sheet', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('answer_sheet_'.str($exam->title)->slug().'.pdf');
    }

    public function previewAnswerSheetHtml(Exam $exam): View
    {
        $exam->load(['mcqs.options']);

        return view('pdf.exam-answer-sheet', $this->pdfData($exam));
    }

    public function previewMarkingGuide(Exam $exam)
    {
        $exam->load(['theoryQuestions']);
        $pdf = Pdf::loadView('pdf.exam-marking-guide', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('marking_guide_'.str($exam->title)->slug().'.pdf');
    }

    public function previewMarkingGuideHtml(Exam $exam): View
    {
        $exam->load(['theoryQuestions']);

        return view('pdf.exam-marking-guide', $this->pdfData($exam));
    }

    private function pdfData(Exam $exam): array
    {
        return [
            'title' => $exam->title,
            'subject' => $exam->subject_name,
            'level' => strtoupper($exam->level instanceof \App\Enums\QuestionLevel ? $exam->level->value : $exam->level),
            'date' => $exam->created_at->format('F j, Y'),
            'instructions' => $exam->instructions,
            'mcqs' => $exam->mcqs ?? collect(),
            'theory' => $exam->theoryQuestions ?? collect(),
            'mcqTotal' => ($exam->mcqs ?? collect())->count(),
            'theoryTotal' => ($exam->theoryQuestions ?? collect())->sum(fn ($q) => collect($q->marking_scheme)->sum('weight')),
            'totalMarks' => $exam->total_marks,
        ];
    }

    private function levelOptions(): array
    {
        return [
            ['value' => 'lp', 'label' => 'Lower Primary'],
            ['value' => 'hp', 'label' => 'Higher Primary'],
            ['value' => 'js', 'label' => 'Junior Secondary'],
            ['value' => 'ss', 'label' => 'Senior Secondary'],
        ];
    }

    private function examPayload(Exam $exam): array
    {
        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'subject' => $exam->subject_name,
            'level' => strtoupper($exam->level instanceof \App\Enums\QuestionLevel ? $exam->level->value : $exam->level),
            'date' => $exam->created_at->format('F j, Y'),
            'instructions' => $exam->instructions,
            'mcq_count' => $exam->mcqs->count(),
            'theory_count' => $exam->theoryQuestions->count(),
            'totalMarks' => $exam->total_marks,
            'mcqs' => $exam->mcqs->map(fn ($q) => [
                'id' => $q->id,
                'content' => $q->content,
                'options' => $q->options->map(fn ($o) => [
                    'id' => $o->id,
                    'content' => $o->content,
                    'is_correct' => $o->is_correct,
                ]),
            ]),
            'theory' => $exam->theoryQuestions->map(fn ($q) => [
                'id' => $q->id,
                'content' => $q->content,
                'marking_scheme' => $q->marking_scheme,
            ]),
        ];
    }
}
