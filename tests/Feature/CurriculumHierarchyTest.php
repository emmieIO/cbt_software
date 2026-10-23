<?php

use App\Ai\Agents\CurriculumTopicAgent;
use App\Models\CurriculumTopicMapping;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\QuestionGenerationRun;
use App\Models\Subject;
use App\Models\Subtopic;
use App\Models\Topic;
use App\Models\User;
use App\Services\CurriculumMappingService;
use App\Services\CurriculumReconciliationService;
use App\Services\CurriculumTopicIdentificationService;
use App\Services\QuestionGenerationContextService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('knowledge.embeddings.provider', 'ollama');
    config()->set('knowledge.embeddings.model', 'embeddinggemma-2');
    config()->set('knowledge.embeddings.dimensions', 768);
    config()->set('knowledge.embeddings.base_url', 'http://ollama.test:11434');
    Http::preventStrayRequests();
    CurriculumTopicAgent::fake()->preventStrayPrompts();
});

test('hierarchical detection deduplicates within paths and preserves rejection across reruns', function () {
    [$document, $topic, $chunk, $reviewer] = hierarchyFixture();
    $base = ['subject_name' => 'Mathematics', 'class_level' => '11', 'hierarchy_uncertain' => false,
        'evidence' => [['chunk_id' => $chunk->id, 'excerpt' => $chunk->content]]];
    $suggestions = [
        [...$base, 'topic_name' => 'Quadratic Equations', 'classification' => 'topic'],
        [...$base, 'topic_name' => 'Solving by factorisation', 'classification' => 'subtopic', 'parent_topic_name' => $topic->name],
        [...$base, 'topic_name' => 'Solving by factorization', 'classification' => 'subtopic', 'parent_topic_name' => $topic->name],
        [...$base, 'topic_name' => 'Common misconceptions', 'classification' => 'topic', 'parent_topic_name' => $topic->name],
    ];
    CurriculumTopicAgent::fake([['topics' => $suggestions], ['topics' => $suggestions]]);
    $service = app(CurriculumTopicIdentificationService::class);
    $service->identify($document, 1);
    expect($document->curriculumMappings()->count())->toBe(3);
    $subtopic = $document->curriculumMappings()->where('classification', 'subtopic')->sole();
    expect($subtopic->topic_id)->toBe($topic->id);
    expect($document->curriculumMappings()->where('classification', 'section')->sole()->detected_topic)->toBe('Common misconceptions');
    app(CurriculumMappingService::class)->review($document, $subtopic, ['action' => 'reject'], $reviewer);
    $document->update(['topic_detection_cursor' => 0]);
    $service->identify($document, 1);
    expect($document->curriculumMappings()->count())->toBe(3)->and($subtopic->refresh()->status)->toBe('rejected')->and($subtopic->reviews()->count())->toBe(1);
});

test('reconciliation is dry run first idempotent and preserves decisions and all evidence', function () {
    [$document, $topic, $chunk, $reviewer] = hierarchyFixture();
    $first = hierarchyPending($document, $topic, $chunk, 'Quadratic equations');
    $second = hierarchyPending($document, $topic, $chunk, 'QUADRATIC equation');
    $rejected = hierarchyPending($document, $topic, $chunk, 'Quadratic Equations', ['status' => 'rejected', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);
    $section = hierarchyPending($document, $topic, $chunk, 'Common misconceptions');
    $service = app(CurriculumReconciliationService::class);
    expect(collect($service->reconcile($document, $reviewer))->where('result', 'merged'))->toHaveCount(1);
    expect($second->refresh()->merged_into_id)->toBeNull();
    $service->reconcile($document, $reviewer, true);
    expect($second->refresh()->merged_into_id)->toBe($first->id)
        ->and($rejected->refresh()->status)->toBe('rejected')
        ->and($section->refresh()->hierarchy_status)->toBe('needs_review')
        ->and($first->evidence()->count())->toBe(1)
        ->and($second->reviews()->count())->toBe(1);
    expect(collect($service->reconcile($document, $reviewer, true))->where('result', 'merged'))->toHaveCount(0);
    $this->artisan('curriculum:reconcile', ['document' => $document->id, '--user' => User::factory()->create()->id])->assertFailed();
});

test('review validates parent relationships and records immutable decision snapshots', function () {
    [$document, $topic, $chunk, $reviewer] = hierarchyFixture();
    $service = app(CurriculumMappingService::class);
    $payload = hierarchyPayload($topic, $chunk);
    $root = $service->review($document, null, $payload, $reviewer);
    $child = $service->review($document, null, [...$payload, 'classification' => 'subtopic', 'new_subtopic_name' => 'Factorization'], $reviewer);
    expect($child->subtopic->topic_id)->toBe($topic->id)->and($child->reviews()->sole()->after_snapshot['classification'])->toBe('subtopic');
    $other = Subtopic::factory()->create();
    $this->actingAs($reviewer)->post(route('curriculum.store', $document), [...$payload, 'classification' => 'subtopic', 'subtopic_id' => $other->id])->assertSessionHasErrors('subtopic_id');
    $this->patch(route('curriculum.update', [$document, $root]), ['action' => 'reject'])->assertRedirect();
    expect($root->reviews()->count())->toBe(2)->and($root->reviews()->oldest()->first()->after_snapshot['status'])->toBe('confirmed');
    expect($service->chunkIds($topic->subject_id, '11', $topic->id, $child->subtopic_id))->toBe([]);
});

test('subtopic generation isolates reviewed passages even when siblings share a chunk', function () {
    [$document, $topic, $chunk, $reviewer] = hierarchyFixture();
    $service = app(CurriculumMappingService::class);
    $payload = hierarchyPayload($topic, $chunk);
    $root = $service->review($document, null, $payload, $reviewer);
    $firstQuote = 'Factorization uses two linear factors.';
    $secondQuote = 'Completing the square adds a constant.';
    $first = $service->review($document, null, [...$payload, 'classification' => 'subtopic', 'new_subtopic_name' => 'Factorization', 'evidence' => [['chunk_id' => $chunk->id, 'excerpt' => $firstQuote]]], $reviewer);
    $second = $service->review($document, null, [...$payload, 'classification' => 'subtopic', 'new_subtopic_name' => 'Completing the square', 'evidence' => [['chunk_id' => $chunk->id, 'excerpt' => $secondQuote]]], $reviewer);
    Http::fake(['ollama.test:11434/api/embed' => Http::response(['embeddings' => [hierarchyVector()]])]);
    $run = QuestionGenerationRun::factory()->create(['subject_id' => $topic->subject_id, 'topic_id' => $topic->id, 'class_level' => '11', 'level' => 'ss', 'subtopic_id' => $first->subtopic_id, 'subtopic_name' => 'Factorization']);
    $context = app(QuestionGenerationContextService::class)->build($run);
    expect($context['context'])->toContain($firstQuote)->not->toContain($secondQuote)->and($context['sources'][0]['page_start'])->toBe(2);
    $run->update(['subtopic_id' => null, 'subtopic_name' => null]);
    expect(app(QuestionGenerationContextService::class)->build($run)['context'])->toContain($firstQuote)->toContain($secondQuote);
    $first->update(['status' => 'suggested']);
    $run->update(['subtopic_id' => $first->subtopic_id]);
    expect(fn () => app(QuestionGenerationContextService::class)->build($run))->toThrow(RuntimeException::class, 'confirmed curriculum coverage');
    Http::assertSentCount(2);
    $document->update(['approval_status' => 'rejected']);
    expect($service->coverage($topic->subject_id, '11', $topic->id))->toHaveCount(0);
});

test('same subtopic can have evidence in multiple documents while stale versions are excluded', function () {
    [$document, $topic, $chunk, $reviewer] = hierarchyFixture();
    $service = app(CurriculumMappingService::class);
    $service->review($document, null, hierarchyPayload($topic, $chunk), $reviewer);
    $child = $service->review($document, null, [...hierarchyPayload($topic, $chunk), 'classification' => 'subtopic', 'new_subtopic_name' => 'Factorization'], $reviewer);
    $otherDocument = $document->replicate();
    $otherDocument->path = 'knowledge-documents/another.pdf';
    $otherDocument->save();
    $otherChunk = $chunk->replicate();
    $otherChunk->knowledge_document_id = $otherDocument->id;
    $otherChunk->save();
    $service->review($otherDocument, null, [...hierarchyPayload($topic, $otherChunk), 'classification' => 'subtopic', 'subtopic_id' => $child->subtopic_id], $reviewer);
    expect($service->chunkIds($topic->subject_id, '11', $topic->id, $child->subtopic_id))->toHaveCount(2);
    $otherDocument->update(['active_chunk_version' => 2]);
    expect($service->chunkIds($topic->subject_id, '11', $topic->id, $child->subtopic_id))->toBe([$chunk->id]);
    Queue::fake();
    $this->actingAs($reviewer)->get(route('question-generations.sources', ['topic_id' => $topic->id, 'subtopic_id' => $child->subtopic_id]))->assertOk()->assertJsonCount(1, 'sources');
});

test('name comparison preserves concept order and mathematical distinctions', function () {
    $service = app(CurriculumMappingService::class);
    expect($service->normalizedName('Factorisation'))->toBe($service->normalizedName('factorization'))
        ->and($service->normalizedName('roots and coefficients'))->not->toBe($service->normalizedName('coefficients and roots'))
        ->and($service->normalizedName('x² + y²'))->not->toBe($service->normalizedName('x² = y²'));
});

test('generation persists subtopic selection and refuses unconfirmed sibling coverage', function () {
    [$document, $topic, $chunk, $reviewer] = hierarchyFixture();
    $service = app(CurriculumMappingService::class);
    $service->review($document, null, hierarchyPayload($topic, $chunk), $reviewer);
    $child = $service->review($document, null, [...hierarchyPayload($topic, $chunk), 'classification' => 'subtopic', 'new_subtopic_name' => 'Factorization'], $reviewer);
    $payload = ['subject_id' => $topic->subject_id, 'topic_id' => $topic->id, 'subtopic_id' => $child->subtopic_id, 'level' => 'ss', 'class_level' => '11', 'quantity' => 1,
        'type_distribution' => ['multiple_choice' => 1, 'short_answer' => 0, 'theory' => 0], 'difficulty' => 'medium'];
    Queue::fake();
    $this->actingAs($reviewer)->post(route('question-generations.store'), $payload)->assertRedirect();
    $this->get(route('question-generations.sources', ['topic_id' => $topic->id]))->assertOk()->assertJsonCount(1, 'sources');
    expect(QuestionGenerationRun::query()->sole()->subtopic_name)->toBe('Factorization')->and(QuestionGenerationRun::query()->sole()->subtopic_id)->toBe($child->subtopic_id);
    $child->update(['status' => 'suggested']);
    $this->post(route('question-generations.store'), $payload)->assertSessionHasErrors('topic_id');
    $this->get(route('question-generations.sources', ['topic_id' => $topic->id, 'subtopic_id' => $child->subtopic_id]))->assertOk()->assertJsonCount(0, 'sources');
    expect(QuestionGenerationRun::query()->count())->toBe(1);
});

test('legacy unresolved subject names reconcile without merging different class contexts', function () {
    [$document, $topic, $chunk, $reviewer] = hierarchyFixture();
    $first = hierarchyPending($document, $topic, $chunk, 'Quadratic equations', ['subject_id' => null, 'topic_id' => null, 'detected_subject' => 'MATHEMATICS']);
    $duplicate = hierarchyPending($document, $topic, $chunk, 'Quadratic equation');
    $otherClass = hierarchyPending($document, $topic, $chunk, 'Quadratic equations', ['class_level' => '10']);
    app(CurriculumReconciliationService::class)->reconcile($document, $reviewer, true);
    expect($duplicate->refresh()->merged_into_id)->toBe($first->id)->and($otherClass->refresh()->merged_into_id)->toBeNull();
    CurriculumTopicAgent::fake([['topics' => [['subject_name' => 'Mathematics', 'class_level' => '11', 'topic_name' => 'Quadratic Equations', 'classification' => 'topic',
        'evidence' => [['chunk_id' => $chunk->id, 'excerpt' => $chunk->content]]]]]]);
    app(CurriculumTopicIdentificationService::class)->identify($document, 1);
    expect($document->curriculumMappings()->count())->toBe(3);
});

test('a document cannot merge another documents mapping or overwrite a reviewed suggestion', function () {
    [$document, $topic, $chunk, $reviewer] = hierarchyFixture();
    $source = hierarchyPending($document, $topic, $chunk, 'Quadratic equations');
    $target = hierarchyPending($document, $topic, $chunk, 'Quadratic equation', ['reviewed_at' => now(), 'reviewed_by' => $reviewer->id]);
    $this->actingAs($reviewer)->patch(route('curriculum.update', [$document, $source]), ['action' => 'merge', 'merge_target_id' => $target->id])->assertSessionHasErrors('merge_target_id');
    $denied = User::factory()->create(['permissions' => []]);
    $this->actingAs($denied)->patch(route('curriculum.update', [$document, $source]), ['action' => 'merge', 'merge_target_id' => $target->id])->assertForbidden();
    expect($source->refresh()->merged_into_id)->toBeNull()->and($target->reviews()->count())->toBe(0);
    $other = KnowledgeDocument::factory()->create();
    $this->actingAs($reviewer)->patch(route('curriculum.update', [$other, $source]), ['action' => 'reject'])->assertNotFound();
});

test('question bank topic deletion reports existing subtopics instead of a database error', function () {
    [, $topic, , $reviewer] = hierarchyFixture();
    Subtopic::factory()->for($topic)->create();
    $this->actingAs($reviewer)->delete(route('topics.destroy', $topic))->assertRedirect()->assertSessionHas('error', 'Cannot delete topic with existing curriculum subtopics.');
    expect($topic->fresh())->not->toBeNull();
});

/** @return array{KnowledgeDocument, Topic, KnowledgeDocumentChunk, User} */
function hierarchyFixture(): array
{
    $subject = Subject::factory()->create(['name' => 'Mathematics', 'level' => 'ss']);
    $topic = Topic::factory()->for($subject)->create(['name' => 'Quadratic Equations', 'class_level' => '11']);
    $document = KnowledgeDocument::factory()->create(['title' => 'SS2 Mathematics: Quadratic Equations', 'approval_status' => 'approved', 'processing_status' => 'completed', 'embedding_status' => 'completed', 'embedding_provider' => 'ollama', 'embedding_model' => 'embeddinggemma-2', 'embedding_dimensions' => 768]);
    $content = 'Factorization uses two linear factors. Completing the square adds a constant.';
    $chunk = KnowledgeDocumentChunk::factory()->create(['knowledge_document_id' => $document->id, 'sequence' => 1, 'content' => $content, 'content_hash' => hash('sha256', $content), 'embedding' => hierarchyVector(), 'embedding_provider' => 'ollama', 'embedding_model' => 'embeddinggemma-2', 'embedding_dimensions' => 768, 'embedded_content_hash' => hash('sha256', $content), 'metadata' => ['page_start' => 2, 'page_end' => 2]]);
    $reviewer = User::factory()->create(['permissions' => [User::PERMISSION_REVIEW_CURRICULUM, User::PERMISSION_CREATE_QUESTIONS, User::PERMISSION_GENERATE_QUESTIONS]]);

    return [$document, $topic, $chunk, $reviewer];
}

/** @return array<string, mixed> */
function hierarchyPayload(Topic $topic, KnowledgeDocumentChunk $chunk): array
{
    return ['action' => 'confirm', 'subject_id' => $topic->subject_id, 'class_level' => $topic->class_level, 'topic_id' => $topic->id, 'evidence' => [['chunk_id' => $chunk->id, 'excerpt' => $chunk->content]]];
}

/** @param array<string, mixed> $attributes */
function hierarchyPending(KnowledgeDocument $document, Topic $topic, KnowledgeDocumentChunk $chunk, string $name, array $attributes = []): CurriculumTopicMapping
{
    $mapping = CurriculumTopicMapping::factory()->create(['knowledge_document_id' => $document->id, 'topic_id' => $topic->id, 'subject_id' => $topic->subject_id, 'class_level' => '11', 'detected_topic' => $name, ...$attributes]);
    $mapping->evidence()->create(['knowledge_document_chunk_id' => $chunk->id, 'content_hash' => $chunk->content_hash, 'excerpt' => $chunk->content]);

    return $mapping;
}

/** @return array<int, float> */
function hierarchyVector(): array
{
    return [1.0, ...array_fill(0, 767, 0.0)];
}
