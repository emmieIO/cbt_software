<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Jobs\GenerateQuestionsFromKnowledgeJob;
use App\Models\GeneratedQuestionDraft;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\Question;
use App\Models\QuestionGenerationRun;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\CurriculumMappingService;
use App\Services\GeneratedQuestionReviewService;
use App\Services\GeneratedQuestionValidator;
use App\Services\QuestionGenerationContextService;
use App\Services\QuestionGenerationService;
use App\Services\QuestionImportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Http::preventStrayRequests();
    QuestionGeneratorAgent::fake()->preventStrayPrompts();
});

test('five reviewed SS2 factorization drafts import once through the normal question bank', function () {
    [$user, $run, $drafts, $document] = giFixture(5, subtopic: true);
    $this->actingAs($user);
    foreach ($drafts as $draft) {
        giApprove($draft);
    }
    $ids = $drafts->modelKeys();
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => $ids])->assertSessionHasNoErrors();
    expect(Question::query()->count())->toBe(5)->and($run->refresh()->import_status)->toBe('imported');
    foreach (Question::query()->with('options', 'provenances')->get() as $question) {
        expect($question->options)->toHaveCount(4)->and($question->options->where('is_correct', true))->toHaveCount(1)
            ->and($question->topic_id)->toBe($run->topic_id)->and($question->class_level)->toBe('11')->and($question->level->value)->toBe('ss')
            ->and($question->subtopic_id)->toBe($run->subtopic_id)->and($question->difficulty)->toBe('medium')->and($question->origin)->toBe('ai_generated')
            ->and($question->created_by)->toBe($user->id)->and($question->provenances)->toHaveCount(1)
            ->and($question->provenances->first()->document_identity)->toBe($document->id);
        $this->get(route('questions.index', ['question_id' => $question->id]))->assertOk();
        $this->get(route('questions.provenance', $question))->assertOk()->assertJsonPath('sources.0.curriculum_snapshot.subtopic', 'Solving by factorization');
    }
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => $ids])->assertRedirect();
    expect(Question::query()->count())->toBe(5)->and($run->refresh()->last_import_summary['already_imported'])->toBe(5);
});

test('the imported batch link filters the normal question bank and preserves pagination', function () {
    [$user, $run, $drafts] = giFixture(2);
    $this->actingAs($user);
    foreach ($drafts as $draft) {
        giApprove($draft);
    }
    app(GeneratedQuestionReviewService::class)->import($run, $user, $drafts->modelKeys());
    Question::factory()->create(['created_by' => $user->id]);

    $this->get(route('questions.index', ['generation_run_id' => $run->id, 'per_page' => 1]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Questions/Index')
            ->where('questions.total', 2)
            ->has('questions.data', 1)
            ->where('filters.generation_run_id', $run->id)
            ->where('questions.next_page_url', fn ($url) => str_contains($url, 'generation_run_id='.$run->id)));

    $this->get(route('questions.index', ['generation_run_id' => 'missing']))
        ->assertInertia(fn (Assert $page) => $page->where('questions.total', 0));
});

test('short answer and theory retain expected answers marking points explanations and correct mappings', function (string $type) {
    [$user, $run, $drafts] = giFixture(1, $type);
    $draft = $drafts->sole();
    $this->actingAs($user);
    giApprove($draft);
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => [$draft->id]])->assertRedirect();
    $question = Question::query()->sole();
    expect($question->type->value)->toBe($type)->and($question->expected_answer)->toBe($draft->expected_answer)
        ->and($question->marking_scheme)->toBe($draft->marking_scheme)->and($question->explanation)->toBe($draft->explanation)
        ->and($question->options()->count())->toBe(0);
})->with(['short_answer', 'theory']);

test('approval requires explicit answer verification and edits invalidate prior approval with audit history', function () {
    [$user, $run, $drafts] = giFixture();
    $draft = $drafts->sole();
    $this->actingAs($user)->post(route('generated-question-drafts.review', $draft), ['action' => 'approve', 'revision' => 1, 'answers_verified' => false])->assertSessionHasErrors();
    giApprove($draft);
    $payload = $draft->fresh()->only(['type', 'difficulty', 'content', 'explanation', 'expected_answer', 'options', 'marking_scheme', 'revision']);
    $payload['content'] = 'Find the roots of x² - 5x + 6 = 0 by factorization.';
    $this->patch(route('generated-question-drafts.update', $draft), $payload)->assertRedirect();
    expect($draft->refresh()->review_status)->toBe('draft')->and($draft->approved_hash)->toBeNull()->and($draft->answers_verified)->toBeFalse()->and($draft->revision)->toBe(2)
        ->and($draft->reviews()->pluck('action')->all())->toContain('approve', 'before_edit', 'edit');
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => [$draft->id]])->assertRedirect();
    expect(Question::query()->count())->toBe(0)->and($run->refresh()->last_import_summary['skipped'])->toBe(1);
    $this->post(route('generated-question-drafts.review', $draft), ['action' => 'approve', 'revision' => 1, 'answers_verified' => true])->assertSessionHasErrors('review');
});

test('saving an MCQ rich text edit preserves math node attributes without formatting HTML as formulas', function () {
    [$user, , $drafts] = giFixture();
    $draft = $drafts->sole();
    $payload = $draft->only(['type', 'difficulty', 'content', 'options', 'explanation', 'revision']);
    $payload['options'][0]['content'] = '<p><span data-type="inline-math" data-latex="x = 2"></span> or <span data-type="inline-math" data-latex="x = 3"></span></p>';
    $this->actingAs($user)->patch(route('generated-question-drafts.update', $draft), $payload)->assertSessionHasNoErrors();
    expect($draft->refresh()->options[0]['content'])->toContain('data-type="inline-math"')->toContain('data-latex="x = 2"')->not->toContain('&gt;');
});

test('rejected and unapproved drafts cannot be imported and imported drafts become immutable', function () {
    [$user, $run, $drafts] = giFixture(3);
    $this->actingAs($user);
    $this->post(route('generated-question-drafts.review', $drafts[0]), ['action' => 'reject', 'revision' => 1])->assertRedirect();
    giApprove($drafts[1]);
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => $drafts->modelKeys()])->assertRedirect();
    expect(Question::query()->count())->toBe(1)->and($run->refresh()->last_import_summary['skipped'])->toBe(2)->and($run->import_status)->toBe('partially_imported');
    $imported = $drafts[1]->fresh();
    $this->post(route('generated-question-drafts.review', $imported), ['action' => 'reject', 'revision' => 1])->assertConflict();
    $this->patch(route('generated-question-drafts.update', $imported), $imported->only(['type', 'difficulty', 'content', 'options', 'explanation']))->assertConflict();
});

test('publication requires create permission and run ownership independently from generation permission', function () {
    [$user, $run, $drafts] = giFixture();
    $draft = $drafts->sole();
    $other = User::factory()->create(['role' => 'admin']);
    $this->actingAs($other)->post(route('question-generations.import-drafts', $run), ['draft_ids' => [$draft->id]])->assertForbidden();
    $this->post(route('generated-question-drafts.review', $draft), ['action' => 'approve', 'revision' => 1, 'answers_verified' => true])->assertForbidden();
    $user->update(['permissions' => [User::PERMISSION_GENERATE_QUESTIONS]]);
    $this->actingAs($user)->post(route('question-generations.import-drafts', $run), ['draft_ids' => [$draft->id]])->assertForbidden();
    expect(Question::query()->count())->toBe(0);
});

test('invalid sources options and marking schemes fail final validation despite a previous valid flag', function (string $problem) {
    [$user, $run, $drafts] = giFixture(1, $problem === 'scheme' ? 'theory' : 'multiple_choice');
    $draft = $drafts->sole();
    if ($problem === 'source') {
        $draft->update(['source_references' => [['chunk_id' => 'unknown']]]);
    } elseif ($problem === 'scheme') {
        $draft->update(['marking_scheme' => [['point' => 'Valid text', 'weight' => 1.5]]]);
    } else {
        $options = $draft->options;
        $options[1]['is_correct'] = true;
        $draft->update(['options' => $options]);
    }
    $this->actingAs($user)->post(route('generated-question-drafts.review', $draft), ['action' => 'approve', 'revision' => 1, 'answers_verified' => true])->assertSessionHasErrors();
    expect($draft->refresh()->review_status)->toBe('draft')->and(Question::query()->count())->toBe(0);
})->with(['source', 'scheme', 'options']);

test('source revocation reprocessing or post approval tampering blocks import', function (string $change) {
    [$user, $run, $drafts, $document] = giFixture();
    $draft = $drafts->sole();
    $this->actingAs($user);
    giApprove($draft);
    match ($change) {
        'rejected' => $document->update(['approval_status' => 'rejected']),
        'version' => $document->update(['active_chunk_version' => 2]),
        'mapping' => $document->curriculumMappings()->update(['status' => 'rejected']),
        'tampering' => $draft->update(['content' => 'A changed question after approval.']),
    };
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => [$draft->id]])->assertRedirect();
    expect(Question::query()->count())->toBe(0)->and($run->refresh()->last_import_summary['failed'])->toBe(1)->and($draft->refresh()->import_errors)->not->toBeEmpty();
})->with(['rejected', 'version', 'mapping', 'tampering']);

test('a partial batch can resume after persistence failure without orphan options or duplicate questions', function () {
    [$user, $run, $drafts] = giFixture(2);
    $this->actingAs($user);
    foreach ($drafts as $draft) {
        giApprove($draft);
    }
    $real = app(QuestionImportService::class);
    $calls = 0;
    $fake = Mockery::mock(QuestionImportService::class)->makePartial();
    $fake->shouldReceive('importResolvedRow')->andReturnUsing(function (...$arguments) use ($real, &$calls) {
        $question = $real->importResolvedRow(...$arguments);
        if (++$calls === 2) {
            throw new RuntimeException('Simulated persistence failure after options');
        }

        return $question;
    });
    app()->instance(QuestionImportService::class, $fake);
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => $drafts->modelKeys()])->assertRedirect();
    expect(Question::query()->count())->toBe(1);
    $this->assertDatabaseCount('options', 4);
    expect($run->refresh()->last_import_summary['failed'])->toBe(1)->and($run->import_status)->toBe('partially_imported');
    app()->instance(QuestionImportService::class, $real);
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => $drafts->modelKeys()])->assertRedirect();
    expect(Question::query()->count())->toBe(2)->and($run->refresh()->import_status)->toBe('imported');
    $this->assertDatabaseCount('options', 8);
});

test('likely question bank duplicates warn but do not silently merge legitimate questions', function () {
    [$user, $run, $drafts] = giFixture();
    $draft = $drafts->sole();
    Question::factory()->create(['topic_id' => $run->topic_id, 'class_level' => '11', 'type' => 'multiple_choice', 'content' => $draft->content]);
    expect(app(GeneratedQuestionReviewService::class)->duplicateWarnings($draft))->toHaveCount(1);
    $this->actingAs($user);
    giApprove($draft);
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => [$draft->id]])->assertRedirect();
    expect(Question::query()->count())->toBe(2);
});

test('provenance survives chunk deletion and private source history is owner restricted', function () {
    [$user, $run, $drafts, $document, $chunk] = giFixture();
    $this->actingAs($user);
    giApprove($drafts->sole());
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => $drafts->modelKeys()]);
    $question = Question::query()->sole();
    $chunk->delete();
    $this->get(route('questions.provenance', $question))->assertOk()->assertJsonPath('sources.0.document_title', $document->title)->assertJsonPath('sources.0.chunk_version', 1);
    $this->actingAs(User::factory()->create(['permissions' => [User::PERMISSION_EDIT_QUESTIONS]]))->get(route('questions.provenance', $question))->assertForbidden();
});

test('database uniqueness and retried generation jobs preserve imported drafts', function () {
    [$user, $run, $drafts] = giFixture();
    $this->actingAs($user);
    giApprove($drafts->sole());
    $this->post(route('question-generations.import-drafts', $run), ['draft_ids' => $drafts->modelKeys()]);
    $job = new GenerateQuestionsFromKnowledgeJob($run->id);
    $job->handle(app(QuestionGenerationContextService::class), app(QuestionGenerationService::class), app(GeneratedQuestionValidator::class));
    $job->failed(new RuntimeException('Delayed old worker failure'));
    expect($run->refresh()->status)->toBe('completed')->and($run->drafts()->sole()->review_status)->toBe('imported');
    QuestionGeneratorAgent::assertNeverPrompted();
    $copy = Question::query()->sole()->replicate();
    expect(fn () => $copy->save())->toThrow(UniqueConstraintViolationException::class);
});

/** @return array{User, QuestionGenerationRun, Collection<int, GeneratedQuestionDraft>, KnowledgeDocument, KnowledgeDocumentChunk} */
function giFixture(int $count = 1, string $type = 'multiple_choice', bool $subtopic = false): array
{
    $user = User::factory()->create(['permissions' => [User::PERMISSION_GENERATE_QUESTIONS, User::PERMISSION_CREATE_QUESTIONS, User::PERMISSION_EDIT_QUESTIONS, User::PERMISSION_REVIEW_CURRICULUM]]);
    $subject = Subject::factory()->create(['name' => 'Mathematics', 'level' => 'ss']);
    $topic = Topic::factory()->for($subject)->create(['name' => 'Quadratic Equations', 'class_level' => '11']);
    $document = KnowledgeDocument::factory()->create(['title' => 'SS2 Mathematics — Quadratic Equations', 'approval_status' => 'approved', 'processing_status' => 'completed', 'embedding_status' => 'completed', 'embedding_provider' => config('knowledge.embeddings.provider'), 'embedding_model' => config('knowledge.embeddings.model'), 'embedding_dimensions' => config('knowledge.embeddings.dimensions')]);
    $content = 'Factorization expresses a quadratic as a product of linear factors. For x² - 5x + 6 = 0, (x - 2)(x - 3) = 0, so x = 2 or x = 3.';
    $hash = hash('sha256', $content);
    $chunk = KnowledgeDocumentChunk::factory()->create(['knowledge_document_id' => $document->id, 'sequence' => 1, 'content' => $content, 'content_hash' => $hash, 'embedded_content_hash' => $hash, 'embedding' => [1.0, ...array_fill(0, (int) config('knowledge.embeddings.dimensions') - 1, 0.0)], 'embedding_provider' => config('knowledge.embeddings.provider'), 'embedding_model' => config('knowledge.embeddings.model'), 'embedding_dimensions' => config('knowledge.embeddings.dimensions')]);
    $payload = ['action' => 'confirm', 'topic_id' => $topic->id, 'subject_id' => $subject->id, 'class_level' => '11', 'evidence' => [['chunk_id' => $chunk->id, 'excerpt' => $content]]];
    app(CurriculumMappingService::class)->review($document, null, $payload, $user);
    $child = $subtopic ? app(CurriculumMappingService::class)->review($document, null, [...$payload, 'classification' => 'subtopic', 'new_subtopic_name' => 'Solving by factorization'], $user) : null;
    $source = ['chunk_id' => $chunk->id, 'chunk_version' => 1, 'content_hash' => $hash, 'document_id' => $document->id, 'document_title' => $document->title, 'page_start' => 1, 'page_end' => 1, 'similarity' => 0.85, 'content' => $content];
    $run = QuestionGenerationRun::factory()->create(['requested_by' => $user->id, 'subject_id' => $subject->id, 'topic_id' => $topic->id, 'level' => 'ss', 'class_level' => '11', 'status' => 'completed', 'requested_count' => $count, 'difficulty' => 'medium', 'source_references' => [$source], 'subtopic_id' => $child?->subtopic_id, 'subtopic_name' => $child?->detected_topic]);
    for ($index = 1; $index <= $count; $index++) {
        $run->drafts()->create(['sequence' => $index, 'type' => $type, 'difficulty' => 'medium', 'content' => 'Question '.$index.': Solve x² - 5x + 6 = 0 by factorization.', 'explanation' => 'Factor as (x - 2)(x - 3).',
            'options' => $type === 'multiple_choice' ? [['content' => '2 and 3', 'is_correct' => true], ['content' => '-2 and -3', 'is_correct' => false], ['content' => '1 and 6', 'is_correct' => false], ['content' => '0 and 5', 'is_correct' => false]] : null,
            'expected_answer' => $type !== 'multiple_choice' ? 'x = 2 or x = 3' : null, 'marking_scheme' => $type !== 'multiple_choice' ? [['point' => 'Factor correctly', 'weight' => 2], ['point' => 'State both roots', 'weight' => 1]] : null,
            'source_references' => [$source], 'is_valid' => true]);
    }

    return [$user, $run, $run->drafts()->get(), $document, $chunk];
}

function giApprove(GeneratedQuestionDraft $draft): void
{
    test()->post(route('generated-question-drafts.review', $draft), ['action' => 'approve', 'revision' => $draft->fresh()->revision, 'answers_verified' => true])->assertSessionHasNoErrors();
}
