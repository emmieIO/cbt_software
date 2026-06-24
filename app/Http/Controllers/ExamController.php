<?php

namespace App\Http\Controllers;

use App\Enums\QuestionLevel;
use App\Http\Requests\Exams\ExamPoolRequest;
use App\Http\Requests\Exams\SaveExamSelectionRequest;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamTitle;
use App\Models\Subject;
use App\Services\ExamGenerationService;
use App\Support\AcademicLevels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(private readonly ExamGenerationService $examGenerationService) {}

    public function index(): Response
    {
        $exams = Exam::query()
            ->with(['academicSession', 'creator', 'questions.topic'])
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function ($e): object {
                $level = $e->level instanceof QuestionLevel ? $e->level->value : $e->level;

                return (object) [
                    'id' => $e->id,
                    'title' => $e->title,
                    'academic_session' => $e->academicSession?->name,
                    'subject_name' => $e->subject_name,
                    'level' => strtoupper($level),
                    'class_level' => AcademicLevels::classLabel($e->class_level, $level),
                    'total_marks' => $e->total_marks,
                    'questions_count' => $e->questions_count,
                    'created_at' => $e->created_at->format('M j, Y'),
                    'creator' => ['name' => $e->creator?->name],
                    'topics' => $e->questions
                        ->map(fn ($question) => $question->topic?->name)
                        ->filter()
                        ->unique()
                        ->values(),
                ];
            });

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
            'classLevels' => AcademicLevels::classOptions(),
            'examTitles' => $this->examTitleOptions(),
            'academicSessions' => $this->academicSessionOptions(),
            'activeAcademicSessionId' => AcademicSession::query()->where('is_active', true)->value('id'),
        ]);
    }

    public function pool(ExamPoolRequest $request): JsonResponse
    {
        return response()->json($this->examGenerationService->pool(
            (string) $request->input('subject_id'),
            (string) $request->input('level'),
            (string) $request->input('class_level'),
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
            'classLevels' => AcademicLevels::classOptions(),
            'examTitles' => $this->examTitleOptions(),
            'academicSessions' => $this->academicSessionOptions($exam->academic_session_id),
            'activeAcademicSessionId' => AcademicSession::query()->where('is_active', true)->value('id'),
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'academic_session_id' => $exam->academic_session_id,
                'academic_session' => $exam->academicSession?->name,
                'subject' => $exam->subject_name,
                'level' => strtoupper($exam->level instanceof QuestionLevel ? $exam->level->value : $exam->level),
                'class_level' => AcademicLevels::classLabel($exam->class_level, $exam->level instanceof QuestionLevel ? $exam->level->value : $exam->level),
                'date' => $exam->created_at->format('F j, Y'),
                'instructions' => $exam->instructions,
                'duration' => $exam->duration,
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
        $exam->load(['academicSession', 'mcqs.options', 'theoryQuestions']);
        $subjects = Subject::query()->with('topics')->orderBy('name')->get();

        return Inertia::render('Exam/Create', [
            'subjects' => $subjects,
            'levels' => $this->levelOptions(),
            'classLevels' => AcademicLevels::classOptions(),
            'examTitles' => $this->examTitleOptions(),
            'academicSessions' => $this->academicSessionOptions($exam->academic_session_id),
            'activeAcademicSessionId' => AcademicSession::query()->where('is_active', true)->value('id'),
            'exam' => $this->examPayload($exam),
        ]);
    }

    public function editQuestions(Exam $exam): Response
    {
        $exam->load(['academicSession', 'questions.topic.subject', 'mcqs.options', 'theoryQuestions']);
        $subjects = Subject::query()->with('topics')->orderBy('name')->get();
        $selectedSubjectId = optional($exam->questions->first()?->topic)->subject_id;
        $level = $exam->level instanceof QuestionLevel ? $exam->level->value : $exam->level;

        return Inertia::render('Exam/Create', [
            'subjects' => $subjects,
            'levels' => $this->levelOptions(),
            'classLevels' => AcademicLevels::classOptions(),
            'examTitles' => $this->examTitleOptions($exam->title),
            'academicSessions' => $this->academicSessionOptions($exam->academic_session_id),
            'activeAcademicSessionId' => AcademicSession::query()->where('is_active', true)->value('id'),
            'exam' => [
                ...$this->examPayload($exam),
                'editable' => true,
                'subject_id' => $selectedSubjectId,
                'selected_question_ids' => $exam->questions->pluck('id')->values(),
            ],
            'initialForm' => [
                'title' => $exam->title,
                'academic_session_id' => $exam->academic_session_id,
                'subject_id' => $selectedSubjectId,
                'level' => $level,
                'class_level' => $exam->class_level ?? AcademicLevels::defaultClassFor($level),
                'instructions' => $exam->instructions,
                'duration' => $exam->duration,
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
        $exam->load(['academicSession', 'mcqs.options', 'theoryQuestions']);
        $pdf = Pdf::loadView('pdf.exam-questions', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->download('questions_'.str($exam->title)->slug().'.pdf');
    }

    public function downloadAnswerKey(Exam $exam)
    {
        $exam->load(['academicSession', 'mcqs.options']);
        $pdf = Pdf::loadView('pdf.exam-answer-key', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->download('answer_key_'.str($exam->title)->slug().'.pdf');
    }

    public function downloadAnswerSheet(Exam $exam)
    {
        $exam->load(['academicSession', 'mcqs.options']);
        $pdf = Pdf::loadView('pdf.exam-answer-sheet', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->download('answer_sheet_'.str($exam->title)->slug().'.pdf');
    }

    public function downloadMarkingGuide(Exam $exam)
    {
        $exam->load(['academicSession', 'theoryQuestions']);
        $pdf = Pdf::loadView('pdf.exam-marking-guide', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->download('marking_guide_'.str($exam->title)->slug().'.pdf');
    }

    public function previewQuestions(Exam $exam)
    {
        $exam->load(['academicSession', 'mcqs.options', 'theoryQuestions']);
        $pdf = Pdf::loadView('pdf.exam-questions', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('questions_'.str($exam->title)->slug().'.pdf');
    }

    public function previewQuestionsHtml(Exam $exam): View
    {
        $exam->load(['academicSession', 'mcqs.options', 'theoryQuestions']);

        return view('staff.exams.print', [
            ...$this->pdfData($exam),
            'examId' => $exam->id,
        ]);
    }

    public function previewAnswerKey(Exam $exam)
    {
        $exam->load(['academicSession', 'mcqs.options']);
        $pdf = Pdf::loadView('pdf.exam-answer-key', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('answer_key_'.str($exam->title)->slug().'.pdf');
    }

    public function previewAnswerKeyHtml(Exam $exam): View
    {
        $exam->load(['academicSession', 'mcqs.options']);

        return view('pdf.exam-answer-key', $this->pdfData($exam));
    }

    public function previewAnswerSheet(Exam $exam)
    {
        $exam->load(['academicSession', 'mcqs.options']);
        $pdf = Pdf::loadView('pdf.exam-answer-sheet', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('answer_sheet_'.str($exam->title)->slug().'.pdf');
    }

    public function previewAnswerSheetHtml(Exam $exam): View
    {
        $exam->load(['academicSession', 'mcqs.options']);

        return view('pdf.exam-answer-sheet', $this->pdfData($exam));
    }

    public function previewMarkingGuide(Exam $exam)
    {
        $exam->load(['academicSession', 'theoryQuestions']);
        $pdf = Pdf::loadView('pdf.exam-marking-guide', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('marking_guide_'.str($exam->title)->slug().'.pdf');
    }

    public function previewMarkingGuideHtml(Exam $exam): View
    {
        $exam->load(['academicSession', 'theoryQuestions']);

        return view('pdf.exam-marking-guide', $this->pdfData($exam));
    }

    private function pdfData(Exam $exam): array
    {
        $mcqs = $exam->mcqs ?? collect();
        $writtenQuestions = $exam->theoryQuestions ?? collect();
        $shortAnswer = $writtenQuestions->filter(fn ($question) => $question->type->value === 'short_answer')->values();
        $theory = $writtenQuestions->reject(fn ($question) => $question->type->value === 'short_answer')->values();

        return [
            'title' => $exam->title,
            'subject' => $exam->subject_name,
            'level' => strtoupper($exam->level instanceof QuestionLevel ? $exam->level->value : $exam->level),
            'classLevel' => AcademicLevels::classLabel($exam->class_level, $exam->level instanceof QuestionLevel ? $exam->level->value : $exam->level),
            'date' => $exam->created_at->format('F j, Y'),
            'instructions' => $exam->instructions,
            'duration' => $exam->duration,
            'academicSession' => $exam->academicSession?->name ?? 'Not set',
            'mcqs' => $mcqs,
            'shortAnswer' => $shortAnswer,
            'theory' => $writtenQuestions,
            'theoryOnly' => $theory,
            'questionSections' => $this->questionSections($mcqs, $shortAnswer, $theory),
            'mcqTotal' => $mcqs->count(),
            'theoryTotal' => $writtenQuestions->sum(fn ($q) => collect($q->marking_scheme)->sum('weight')),
            'totalMarks' => $exam->total_marks,
        ];
    }

    /**
     * @return Collection<int, array{label: string, title: string, note: string, questions: Collection<int, mixed>, type: string, start: int}>
     */
    private function questionSections(Collection $mcqs, Collection $shortAnswer, Collection $theory): Collection
    {
        $nextQuestionNumber = 1;
        $nextSectionIndex = 0;

        return collect([
            [
                'title' => 'Multiple Choice',
                'note' => 'Choose the correct option from A to D for each question.',
                'questions' => $mcqs,
                'type' => 'mcq',
            ],
            [
                'title' => 'Short Answer',
                'note' => 'Answer each question briefly and clearly.',
                'questions' => $shortAnswer,
                'type' => 'short_answer',
            ],
            [
                'title' => 'Theory',
                'note' => 'Answer all questions clearly. Show all necessary workings.',
                'questions' => $theory,
                'type' => 'theory',
            ],
        ])
            ->filter(fn (array $section) => $section['questions']->isNotEmpty())
            ->values()
            ->map(function (array $section) use (&$nextQuestionNumber, &$nextSectionIndex): array {
                $section['label'] = 'Section '.chr(65 + $nextSectionIndex);
                $section['start'] = $nextQuestionNumber;
                $nextQuestionNumber += $section['questions']->count();
                $nextSectionIndex++;

                return $section;
            });
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

    /**
     * @return array<int, string>
     */
    private function examTitleOptions(?string $currentTitle = null): array
    {
        $titles = ExamTitle::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if ($currentTitle && ! in_array($currentTitle, $titles, true)) {
            array_unshift($titles, $currentTitle);
        }

        return $titles;
    }

    /**
     * @return array<int, array{id: string, name: string, is_active: bool}>
     */
    private function academicSessionOptions(?string $currentSessionId = null): array
    {
        return AcademicSession::query()
            ->where(function ($query) use ($currentSessionId): void {
                $query->where('is_active', true)
                    ->when($currentSessionId, fn ($query) => $query->orWhere('id', $currentSessionId));
            })
            ->orderByDesc('starts_at')
            ->get(['id', 'name', 'is_active'])
            ->map(fn (AcademicSession $session): array => [
                'id' => $session->id,
                'name' => $session->name,
                'is_active' => $session->is_active,
            ])
            ->all();
    }

    private function examPayload(Exam $exam): array
    {
        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'academic_session_id' => $exam->academic_session_id,
            'academic_session' => $exam->academicSession?->name,
            'subject' => $exam->subject_name,
            'level' => strtoupper($exam->level instanceof QuestionLevel ? $exam->level->value : $exam->level),
            'class_level' => AcademicLevels::classLabel($exam->class_level, $exam->level instanceof QuestionLevel ? $exam->level->value : $exam->level),
            'date' => $exam->created_at->format('F j, Y'),
            'instructions' => $exam->instructions,
            'duration' => $exam->duration,
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
