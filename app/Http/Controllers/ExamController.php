<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function index(): Response
    {
        $exams = Exam::query()
            ->with('creator')
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

    public function pool(Request $request): JsonResponse
    {
        $subjectId = $request->input('subject_id');
        $level = $request->input('level');

        if (! $subjectId || ! $level) {
            return response()->json(['error' => 'subject_id and level required'], 422);
        }

        $mcqs = Question::query()
            ->where('type', 'multiple_choice')
            ->where('level', $level)
            ->whereHas('topic', fn ($q) => $q->where('subject_id', $subjectId))
            ->with('options')
            ->orderBy('used_count')
            ->orderBy('last_used_at')
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'content' => $q->content,
                'used_count' => $q->used_count,
                'last_used_at' => $q->last_used_at?->diffForHumans(),
                'topic' => $q->topic?->name,
                'type' => 'mcq',
                'options' => $q->options->map(fn ($o) => [
                    'id' => $o->id,
                    'content' => $o->content,
                    'is_correct' => $o->is_correct,
                ]),
            ]);

        $theory = Question::query()
            ->where('type', 'theory')
            ->where('level', $level)
            ->whereHas('topic', fn ($q) => $q->where('subject_id', $subjectId))
            ->orderBy('used_count')
            ->orderBy('last_used_at')
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'content' => $q->content,
                'used_count' => $q->used_count,
                'last_used_at' => $q->last_used_at?->diffForHumans(),
                'topic' => $q->topic?->name,
                'type' => 'theory',
                'marking_scheme' => $q->marking_scheme,
            ]);

        return response()->json(['mcqs' => $mcqs, 'theory' => $theory]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'exists:questions,id',
        ]);

        $questions = Question::query()
            ->whereIn('id', $validated['question_ids'])
            ->with('options')
            ->get();

        $mcqs = $questions->where('type', 'multiple_choice');
        $theory = $questions->where('type', 'theory');

        $subjectName = $mcqs->first()?->topic?->subject?->name
            ?? $theory->first()?->topic?->subject?->name
            ?? 'General';

        $level = $mcqs->first()?->level
            ?? $theory->first()?->level
            ?? 'js';
        $levelValue = $level instanceof \App\Enums\QuestionLevel ? $level->value : $level;

        $exam = Exam::query()->create([
            'title' => $validated['title'],
            'subject_name' => $subjectName,
            'level' => $levelValue,
            'instructions' => $validated['instructions'] ?? 'Answer all questions carefully.',
            'mcq_count' => $mcqs->count(),
            'theory_count' => $theory->count(),
            'total_marks' => $mcqs->count() + $theory->sum(fn ($q) => collect($q->marking_scheme)->sum('weight')),
            'created_by' => $request->user()->id,
        ]);

        foreach ($mcqs as $i => $q) {
            $exam->questions()->attach($q->id, ['section' => 'mcq', 'sort_order' => $i]);
            $q->markAsUsed();
        }

        foreach ($theory as $i => $q) {
            $exam->questions()->attach($q->id, ['section' => 'theory', 'sort_order' => $i]);
            $q->markAsUsed();
        }

        $exam->load(['mcqs.options', 'theoryQuestions']);
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

    public function updateQuestions(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'exists:questions,id',
        ]);

        $questions = Question::query()
            ->whereIn('id', $validated['question_ids'])
            ->with(['options', 'topic.subject'])
            ->get();

        $mcqs = $questions->where('type', 'multiple_choice')->values();
        $theory = $questions->where('type', 'theory')->values();
        $subjectName = $mcqs->first()?->topic?->subject?->name
            ?? $theory->first()?->topic?->subject?->name
            ?? $exam->subject_name;
        $level = $mcqs->first()?->level
            ?? $theory->first()?->level
            ?? $exam->level;
        $levelValue = $level instanceof \App\Enums\QuestionLevel ? $level->value : $level;

        $exam->update([
            'title' => $validated['title'],
            'subject_name' => $subjectName,
            'level' => $levelValue,
            'instructions' => $validated['instructions'] ?? 'Answer all questions carefully.',
            'mcq_count' => $mcqs->count(),
            'theory_count' => $theory->count(),
            'total_marks' => $mcqs->count() + $theory->sum(fn ($q) => collect($q->marking_scheme)->sum('weight')),
        ]);

        $syncData = [];
        foreach ($mcqs as $i => $q) {
            $syncData[$q->id] = ['section' => 'mcq', 'sort_order' => $i];
        }
        foreach ($theory as $i => $q) {
            $syncData[$q->id] = ['section' => 'theory', 'sort_order' => $i];
        }

        $exam->questions()->sync($syncData);

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

    public function previewAnswerKey(Exam $exam)
    {
        $exam->load(['mcqs.options']);
        $pdf = Pdf::loadView('pdf.exam-answer-key', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('answer_key_'.str($exam->title)->slug().'.pdf');
    }

    public function previewMarkingGuide(Exam $exam)
    {
        $exam->load(['theoryQuestions']);
        $pdf = Pdf::loadView('pdf.exam-marking-guide', $this->pdfData($exam));
        $pdf->setPaper('a4');

        return $pdf->stream('marking_guide_'.str($exam->title)->slug().'.pdf');
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
