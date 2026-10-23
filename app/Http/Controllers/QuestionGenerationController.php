<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionGeneration\ImportGeneratedQuestionsRequest;
use App\Http\Requests\QuestionGeneration\StoreQuestionGenerationRequest;
use App\Models\CurriculumTopicMapping;
use App\Models\QuestionGenerationRun;
use App\Models\Subject;
use App\Models\Subtopic;
use App\Models\Topic;
use App\Services\CurriculumMappingService;
use App\Services\GeneratedQuestionReviewService;
use App\Services\QuestionGenerationService;
use App\Support\AcademicLevels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionGenerationController extends Controller
{
    public function index(Request $request, CurriculumMappingService $mappingService): Response
    {
        abort_unless($request->user()?->canGenerateQuestions(), 403);

        $coverage = $mappingService->coverage();
        $coveredIds = $coverage->pluck('topic_id')->unique()->all();
        $subjects = Subject::query()
            ->whereHas('topics', fn ($query) => $query->whereIn('id', $coveredIds))
            ->with(['topics' => fn ($query) => $query->whereIn('id', $coveredIds)->orderBy('name'), 'topics.subtopics'])
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->map(fn (Subject $subject): array => [
                'id' => $subject->id,
                'name' => $subject->name,
                'level' => $subject->level,
                'topics' => $subject->topics->map(fn (Topic $topic): array => [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'class_level' => $topic->class_level,
                    'document_count' => $coverage->where('topic_id', $topic->id)->pluck('knowledge_document_id')->unique()->count(),
                    'subtopics' => $topic->subtopics->filter(fn (Subtopic $subtopic): bool => $coverage->where('subtopic_id', $subtopic->id)->isNotEmpty())->map(fn (Subtopic $subtopic): array => [
                        'id' => $subtopic->id, 'name' => $subtopic->name,
                        'document_count' => $coverage->where('subtopic_id', $subtopic->id)->pluck('knowledge_document_id')->unique()->count(),
                    ])->values(),
                ])->values(),
            ]);

        $runs = QuestionGenerationRun::query()
            ->where('requested_by', $request->user()->id)
            ->with(['subject:id,name', 'topic:id,name'])
            ->withCount(['drafts', 'drafts as valid_drafts_count' => fn ($query) => $query->where('is_valid', true)])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('QuestionGeneration/Index', [
            'subjects' => $subjects,
            'runs' => $runs,
            'levels' => AcademicLevels::levelOptions(),
            'classLevels' => AcademicLevels::classOptions(),
            'maxQuestions' => (int) config('knowledge.generation.max_questions', 30),
        ]);
    }

    public function sources(Request $request, CurriculumMappingService $mappingService): JsonResponse
    {
        abort_unless($request->user()?->canGenerateQuestions(), 403);
        $topicId = $request->query('topic_id');
        abort_unless(is_string($topicId) && strlen($topicId) === 26, 422);
        $topic = Topic::query()->findOrFail($topicId);
        abort_unless($topic->class_level !== null, 422);
        $data = $request->validate(['subtopic_id' => ['nullable', 'string', 'exists:subtopics,id']]);
        $subtopicId = $data['subtopic_id'] ?? null;
        abort_if($subtopicId && ! $topic->subtopics()->whereKey($subtopicId)->exists(), 422);
        $allowedChunks = $mappingService->chunkIds($topic->subject_id, $topic->class_level, $topic->id, $subtopicId);
        $mappings = $mappingService->coverage($topic->subject_id, $topic->class_level, $topic->id, $subtopicId);

        $sources = $mappings->map(fn (CurriculumTopicMapping $mapping): array => [
            'document_id' => $mapping->knowledge_document_id,
            'document_title' => $mapping->document->title,
            'evidence' => $mapping->evidence->filter(fn ($item) => in_array($item->knowledge_document_chunk_id, $allowedChunks, true))->map(fn ($item): array => [
                'chunk_id' => $item->knowledge_document_chunk_id, 'excerpt' => $item->excerpt,
                'page_start' => $item->page_start, 'page_end' => $item->page_end,
            ])->values(),
        ])->filter(fn (array $source): bool => $source['evidence']->isNotEmpty())
            ->groupBy('document_id')->map(fn ($group): array => [
                'document_id' => $group->first()['document_id'],
                'document_title' => $group->first()['document_title'],
                'evidence' => $group->flatMap(fn (array $source) => $source['evidence'])
                    ->unique(fn (array $quote): string => $quote['chunk_id'].'|'.$quote['excerpt'])->values(),
            ])->values();

        return response()->json(['sources' => $sources]);
    }

    public function store(StoreQuestionGenerationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $run = QuestionGenerationRun::query()->create([
            'requested_by' => $request->user()->id,
            'subject_id' => $data['subject_id'],
            'topic_id' => $data['topic_id'],
            'subtopic_id' => $data['subtopic_id'] ?? null,
            'subtopic_name' => filled($data['subtopic_id'] ?? null) ? Subtopic::query()->findOrFail($data['subtopic_id'])->name : null,
            'level' => $data['level'],
            'class_level' => $data['class_level'],
            'requested_count' => $data['quantity'],
            'type_distribution' => $data['type_distribution'],
            'difficulty' => $data['difficulty'],
            'additional_instructions' => filled($data['additional_instructions'] ?? null)
                ? trim($data['additional_instructions'])
                : null,
            'status' => QuestionGenerationRun::STATUS_PENDING,
        ]);

        if (! app(QuestionGenerationService::class)->queue($run)) {
            return to_route('question-generations.show', $run)->with('error', 'Question generation could not be queued. Please retry when the queue service is available.');
        }

        return to_route('question-generations.show', $run)
            ->with('success', 'Question generation has been queued.');
    }

    public function show(Request $request, QuestionGenerationRun $questionGenerationRun, GeneratedQuestionReviewService $service): Response
    {
        $this->authorizeOwner($request, $questionGenerationRun);
        $questionGenerationRun->load(['subject:id,name', 'topic:id,name', 'drafts.editor:id,name', 'drafts.reviewer:id,name', 'drafts.importer:id,name']);
        foreach ($questionGenerationRun->drafts as $draft) {
            $draft->setRelation('run', $questionGenerationRun);
            $draft->setAttribute('duplicate_warnings', $service->duplicateWarnings($draft));
            $draft->unsetRelation('run');
        }

        return Inertia::render('QuestionGeneration/Show', [
            'run' => $questionGenerationRun,
            'canImport' => $request->user()?->canCreateQuestions(),
            'canEditBank' => $request->user()?->canEditQuestions(),
        ]);
    }

    public function importDrafts(ImportGeneratedQuestionsRequest $request, QuestionGenerationRun $questionGenerationRun, GeneratedQuestionReviewService $service): RedirectResponse
    {
        $summary = $service->import($questionGenerationRun, $request->user(), $request->validated('draft_ids'));

        return back()->with('success', $summary['imported'].' questions imported; '.$summary['failed'].' failed.');
    }

    public function retry(Request $request, QuestionGenerationRun $questionGenerationRun): RedirectResponse
    {
        $this->authorizeOwner($request, $questionGenerationRun);
        abort_unless($questionGenerationRun->status === QuestionGenerationRun::STATUS_FAILED, 422);
        abort_if($questionGenerationRun->drafts()->exists(), 422, 'Existing drafts are preserved. Create a new generation instead.');

        if (! app(QuestionGenerationService::class)->queue($questionGenerationRun)) {
            return back()->with('error', 'Question generation could not be queued. Please retry when the queue service is available.');
        }

        return back()->with('success', 'Question generation has been queued again.');
    }

    private function authorizeOwner(Request $request, QuestionGenerationRun $run): void
    {
        abort_unless(
            ($request->user()?->canGenerateQuestions() ?? false)
                && $run->requested_by === $request->user()?->id,
            403,
        );
    }
}
