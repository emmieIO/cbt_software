<?php

namespace App\Services;

use App\Models\GeneratedQuestionDraft;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\Question;
use App\Models\QuestionGenerationRun;
use App\Models\User;
use App\Support\AcademicLevels;
use App\Support\RichContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class GeneratedQuestionReviewService
{
    public function __construct(private readonly CurriculumMappingService $curriculum, private readonly QuestionImportService $importer) {}

    public function authorize(User $user, QuestionGenerationRun $run, bool $import = false): void
    {
        abort_unless($user->canGenerateQuestions() && $run->requested_by === $user->id && (! $import || $user->canCreateQuestions()), 403);
    }

    public function fingerprint(GeneratedQuestionDraft $draft): string
    {
        return hash('sha256', json_encode([$draft->only(['type', 'difficulty', 'content', 'explanation', 'expected_answer', 'options', 'marking_scheme', 'source_references', 'revision']),
            $draft->run->only(['subject_id', 'topic_id', 'subtopic_id', 'subtopic_name', 'level', 'class_level'])], JSON_THROW_ON_ERROR));
    }

    public function audit(GeneratedQuestionDraft $draft, User $actor, string $action): void
    {
        $draft->reviews()->create(['actor_id' => $actor->id, 'action' => $action, 'snapshot' => $draft->getAttributes()]);
    }

    /** @param array<string, mixed> $data */
    public function review(GeneratedQuestionDraft $draft, User $user, array $data): void
    {
        DB::transaction(function () use ($draft, $user, $data): void {
            $run = QuestionGenerationRun::query()->lockForUpdate()->findOrFail($draft->question_generation_run_id);
            $this->authorize($user, $run);
            $draft = $run->drafts()->lockForUpdate()->findOrFail($draft->id);
            abort_if($draft->review_status === 'imported' || $draft->question_id !== null, 409, 'Imported drafts cannot be reviewed again.');
            if ($draft->revision !== $data['revision']) {
                throw ValidationException::withMessages(['review' => 'The draft changed. Reload and review the latest revision.']);
            }
            $approve = $data['action'] === 'approve';
            if ($approve && ! ($data['answers_verified'] ?? false)) {
                throw ValidationException::withMessages(['answers_verified' => 'Verify the correct answer and marking scheme before approving.']);
            }
            if ($approve) {
                $this->validateDraft($draft);
            }
            $draft->update(['review_status' => $approve ? 'approved' : 'rejected', 'reviewed_by' => $user->id, 'reviewed_at' => now(),
                'approved_hash' => $approve ? $this->fingerprint($draft) : null, 'answers_verified' => $approve && ($data['answers_verified'] ?? false),
                'is_valid' => $approve ? true : $draft->is_valid, 'import_errors' => null]);
            $this->audit($draft, $user, $data['action']);
        });
    }

    public function validateDraft(GeneratedQuestionDraft $draft, bool $currentSources = true): void
    {
        $data = $draft->attributesToArray();
        $rules = ['type' => 'required|in:multiple_choice,short_answer,theory', 'difficulty' => 'required|in:easy,medium,hard',
            'content' => 'required|string|max:20000', 'explanation' => 'nullable|string|max:20000', 'expected_answer' => 'nullable|string|max:20000'];
        if ($draft->type === 'multiple_choice') {
            $rules += ['options' => 'required|array|size:4', 'options.*.content' => 'required|string|max:5000', 'options.*.is_correct' => 'required|boolean'];
        } else {
            $rules += ['expected_answer' => 'required|string|max:20000', 'marking_scheme' => 'required|array|min:1|max:100', 'marking_scheme.*.point' => 'required|string|max:5000', 'marking_scheme.*.weight' => 'required|integer|min:1|max:100'];
        }
        Validator::make($data, $rules)->validate();
        $errors = [];
        if (RichContent::text($draft->content) === '') {
            $errors[] = 'Question content is empty.';
        }
        if ($draft->type === 'multiple_choice') {
            $options = collect($draft->options);
            if ($options->where('is_correct', true)->count() !== 1) {
                $errors[] = 'Choose exactly one correct option.';
            }
            $texts = $options->map(fn ($option): string => mb_strtolower(trim(RichContent::text($option['content']))));
            if ($texts->contains('') || $texts->unique()->count() !== 4) {
                $errors[] = 'Options must be nonempty and distinct.';
            }
        } else {
            if (RichContent::text($draft->expected_answer) === '' || collect($draft->marking_scheme)->contains(fn ($point): bool => RichContent::text($point['point']) === '')) {
                $errors[] = 'The answer and every marking point require readable content.';
            }
        }
        $run = $draft->run()->with(['topic.subject', 'subtopic'])->firstOrFail();
        if (! $run->topic || $run->topic->subject_id !== $run->subject_id || $run->topic->class_level !== $run->class_level
            || $run->topic->subject?->level !== $run->level || ! AcademicLevels::classBelongsToLevel($run->class_level, $run->level)
            || ($run->subtopic_id && $run->subtopic?->topic_id !== $run->topic_id) || ($run->subtopic_name && ! $run->subtopic_id)) {
            $errors[] = 'Subject, class or topic association is no longer valid.';
        }
        $sourceMap = collect($run->source_references ?? [])->keyBy('chunk_id');
        $references = $draft->source_references ?? [];
        if ($references === []) {
            $errors[] = 'At least one source reference is required.';
        }
        if ($currentSources && DB::transactionLevel() > 0) {
            KnowledgeDocument::query()->whereIn('id', array_column($references, 'document_id'))->orderBy('id')->lockForUpdate()->get();
        }
        $coverage = $currentSources ? $this->curriculum->coverage($run->subject_id, $run->class_level, $run->topic_id, $run->subtopic_id) : collect();
        foreach ($references as $reference) {
            $source = $sourceMap->get($reference['chunk_id'] ?? '');
            if (! $source || ($source['document_id'] ?? null) !== ($reference['document_id'] ?? null)
                || ($source['chunk_version'] ?? null) !== ($reference['chunk_version'] ?? null)
                || ($source['content'] ?? null) !== ($reference['content'] ?? null)
                || ($source['page_start'] ?? null) !== ($reference['page_start'] ?? null)
                || ($source['page_end'] ?? null) !== ($reference['page_end'] ?? null)) {
                $errors[] = 'A source reference does not match the generation evidence.';

                continue;
            }
            if (! $currentSources) {
                continue;
            }
            $chunk = KnowledgeDocumentChunk::query()->with('document')->find($reference['chunk_id']);
            $mapped = $coverage->flatMap(fn ($mapping) => $mapping->evidence)->contains(fn ($evidence): bool => $evidence->knowledge_document_chunk_id === ($reference['chunk_id'] ?? null)
                && str_contains($this->curriculum->normalizeText($evidence->excerpt), $this->curriculum->normalizeText($source['content'] ?? '')));
            if (! $mapped) {
                // A source can contain several reviewed excerpts from the same chunk.
                $passages = $coverage->flatMap(fn ($mapping) => $mapping->evidence)->where('knowledge_document_chunk_id', $reference['chunk_id'])->pluck('excerpt')->unique()->implode("\n\n");
                $mapped = $passages !== '' && $this->curriculum->normalizeText($passages) === $this->curriculum->normalizeText($source['content'] ?? '');
            }
            if (! $chunk || ! $mapped || $chunk->chunk_version !== ($reference['chunk_version'] ?? 0) || $chunk->document?->active_chunk_version !== $chunk->chunk_version
                || $chunk->knowledge_document_id !== $reference['document_id'] || $chunk->embedding === null
                || $chunk->embedded_content_hash !== $chunk->content_hash || $chunk->embedding_provider !== config('knowledge.embeddings.provider')
                || $chunk->embedding_model !== config('knowledge.embeddings.model') || $chunk->embedding_dimensions !== (int) config('knowledge.embeddings.dimensions')
                || (isset($source['content_hash']) && $source['content_hash'] !== $chunk->content_hash)) {
                $errors[] = 'A cited source is stale, unapproved, unembedded, or no longer mapped to this curriculum.';
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['draft' => array_values(array_unique($errors))]);
        }
    }

    /** @return array<int, array{id: string, content: string}> */
    public function duplicateWarnings(GeneratedQuestionDraft $draft): array
    {
        $key = mb_strtolower($this->curriculum->normalizeText(RichContent::text($draft->content)));

        return Question::query()->where('topic_id', $draft->run->topic_id)->where('class_level', $draft->run->class_level)
            ->where('type', $draft->type)->when($draft->question_id, fn ($q) => $q->whereKeyNot($draft->question_id))->latest()->limit(500)->get(['id', 'content'])
            ->filter(function (Question $question) use ($key): bool {
                $candidate = mb_strtolower($this->curriculum->normalizeText(RichContent::text($question->content)));
                similar_text($key, $candidate, $similarity);

                return $key === $candidate || $similarity >= 92;
            })->map(fn (Question $question): array => ['id' => $question->id, 'content' => mb_substr(RichContent::text($question->content), 0, 180)])->values()->all();
    }

    /** @param array<int, string> $ids @return array<string, mixed> */
    public function import(QuestionGenerationRun $run, User $user, array $ids): array
    {
        $this->authorize($user, $run, true);
        abort_if(count($ids) > 30 || count($ids) !== count(array_unique($ids)), 422);
        abort_unless($run->drafts()->whereIn('id', $ids)->count() === count($ids), 404);
        $summary = ['requested' => count($ids), 'imported' => 0, 'already_imported' => 0, 'skipped' => 0, 'failed' => 0, 'results' => []];
        foreach ($ids as $id) {
            try {
                $result = DB::transaction(function () use ($run, $user, $id): array {
                    $lockedRun = QuestionGenerationRun::query()->lockForUpdate()->findOrFail($run->id);
                    $this->authorize($user, $lockedRun, true);
                    $draft = $lockedRun->drafts()->lockForUpdate()->findOrFail($id);
                    $existing = Question::withTrashed()->where('generated_draft_id', $draft->id)->first();
                    if ($existing) {
                        return ['status' => 'already_imported', 'draft_id' => $id, 'question_id' => $existing->id];
                    }
                    if ($draft->review_status !== 'approved') {
                        return ['status' => 'skipped', 'draft_id' => $id, 'errors' => ['Approve this draft before importing.']];
                    }
                    if (! $draft->answers_verified || ! hash_equals($draft->approved_hash ?? '', $this->fingerprint($draft))) {
                        throw ValidationException::withMessages(['draft' => 'The approved answer or draft has changed. Review it again.']);
                    }
                    $this->validateDraft($draft);
                    $question = $this->importer->importResolvedRow([
                        'content' => $draft->content, 'explanation' => $draft->explanation, 'type' => $draft->type, 'difficulty' => $draft->difficulty,
                        'expected_answer' => $draft->expected_answer, 'marking_scheme' => $draft->marking_scheme,
                        'options' => array_column($draft->options ?? [], 'content'),
                        'correct_answer' => chr(97 + (int) collect($draft->options ?? [])->search(fn ($option): bool => (bool) $option['is_correct'])),
                        'subtopic_id' => $lockedRun->subtopic_id, 'generated_draft_id' => $draft->id,
                    ], $lockedRun->topic_id, $lockedRun->level, $lockedRun->class_level, $user->id, 'ai_generated');
                    foreach ($draft->source_references as $source) {
                        $document = KnowledgeDocument::query()->findOrFail($source['document_id']);
                        $chunk = KnowledgeDocumentChunk::query()->findOrFail($source['chunk_id']);
                        $mapping = $this->curriculum->coverage($lockedRun->subject_id, $lockedRun->class_level, $lockedRun->topic_id, $lockedRun->subtopic_id)
                            ->first(fn ($mapping): bool => $mapping->evidence->contains('knowledge_document_chunk_id', $chunk->id));
                        $question->provenances()->create([
                            'question_generation_run_id' => $lockedRun->id, 'generated_question_draft_id' => $draft->id,
                            'knowledge_document_id' => $document->id, 'document_identity' => $document->id, 'document_title' => $document->title,
                            'original_name' => $document->original_name, 'document_checksum' => $document->checksum, 'chunk_identity' => $chunk->id,
                            'chunk_version' => $chunk->chunk_version, 'content_hash' => $chunk->content_hash,
                            'page_start' => $source['page_start'] ?? null, 'page_end' => $source['page_end'] ?? null, 'curriculum_topic_mapping_id' => $mapping?->id,
                            'curriculum_snapshot' => ['subject' => $lockedRun->subject->name, 'topic' => $lockedRun->topic->name, 'subtopic' => $lockedRun->subtopic_name, 'class_level' => $lockedRun->class_level, 'level' => $lockedRun->level],
                        ]);
                    }
                    $draft->update(['review_status' => 'imported', 'question_id' => $question->id, 'imported_by' => $user->id, 'imported_at' => now(), 'import_errors' => null]);
                    $this->audit($draft, $user, 'import');
                    $count = $lockedRun->drafts()->where('review_status', 'imported')->count();
                    $lockedRun->update(['import_status' => $count === $lockedRun->drafts()->count() ? 'imported' : 'partially_imported']);

                    return ['status' => 'imported', 'draft_id' => $id, 'question_id' => $question->id];
                });
                $summary[$result['status']]++;
            } catch (Throwable $exception) {
                $messages = $exception instanceof ValidationException ? $exception->validator->errors()->all() : ['Import failed. The draft and question bank were left unchanged.'];
                if (! $exception instanceof ValidationException) {
                    report($exception);
                }
                $run->drafts()->whereKey($id)->whereNull('question_id')->update(['import_errors' => json_encode($messages, JSON_THROW_ON_ERROR)]);
                $summary['failed']++;
                $result = ['status' => 'failed', 'draft_id' => $id, 'errors' => $messages];
            }
            $summary['results'][] = $result;
        }
        $run->update(['last_import_summary' => $summary]);

        return $summary;
    }
}
