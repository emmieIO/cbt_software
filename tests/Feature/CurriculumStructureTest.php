<?php

use App\Models\CurriculumTopicMapping;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\Subject;
use App\Models\Subtopic;
use App\Models\Topic;
use App\Models\User;
use App\Services\CurriculumMappingService;
use App\Services\CurriculumStructureService;

test('one confirmation creates the main topic and subtopics and makes them available together', function () {
    [$document, $user, $payload] = structureFixture();
    $this->actingAs($user)->post(route('curriculum.confirm-structure', $document), $payload)->assertRedirect()->assertSessionHasNoErrors();
    expect(Topic::query()->count())->toBe(1)->and(Subtopic::query()->count())->toBe(2)
        ->and($document->curriculumMappings()->where('status', 'confirmed')->count())->toBe(3);
    $root = $document->curriculumMappings()->where('classification', 'topic')->sole();
    expect(app(CurriculumMappingService::class)->coverage($root->subject_id, '11', $root->topic_id))->toHaveCount(3);
    foreach ($document->curriculumMappings()->get() as $mapping) {
        expect($mapping->reviewed_by)->toBe($user->id)->and($mapping->reviews()->count())->toBe(1);
    }
    $this->get(route('question-generations.index'))->assertOk();
});

test('a failing last child rolls back topics subtopics evidence and review history', function () {
    [$document, $user, $payload] = structureFixture();
    $payload['children'][1]['evidence'][0]['excerpt'] = 'This quotation is not in the lesson note.';
    $this->actingAs($user)->post(route('curriculum.confirm-structure', $document), $payload)->assertSessionHasErrors('children.1.name');
    expect(Topic::query()->count())->toBe(0)->and(Subtopic::query()->count())->toBe(0)
        ->and($document->curriculumMappings()->where('status', 'confirmed')->count())->toBe(0);
    $this->assertDatabaseCount('curriculum_mapping_reviews', 0);
});

test('one button merges duplicate pending suggestions and preserves their history and evidence', function () {
    [$document, $user, $payload, $chunk] = structureFixture();
    $duplicate = $document->curriculumMappings()->findOrFail($payload['children'][0]['mapping_ids'][0])->replicate();
    $duplicate->identity_key = hash('sha256', 'duplicate-child');
    $duplicate->save();
    $duplicate->evidence()->create(['knowledge_document_chunk_id' => $chunk->id, 'content_hash' => $chunk->content_hash, 'excerpt' => $payload['children'][0]['evidence'][0]['excerpt']]);
    $payload['children'][0]['mapping_ids'][] = $duplicate->id;
    $payload['review_token'] = app(CurriculumStructureService::class)->reviewToken($document);
    $this->actingAs($user)->post(route('curriculum.confirm-structure', $document), $payload)->assertSessionHasNoErrors();
    expect($duplicate->refresh()->merged_into_id)->toBe($payload['children'][0]['mapping_ids'][0])
        ->and($duplicate->reviews()->sole()->action)->toBe('structure_merge');
    $this->post(route('curriculum.confirm-structure', $document), $payload)->assertSessionHasErrors('structure');
    expect(Topic::query()->count())->toBe(1)->and(Subtopic::query()->count())->toBe(2);
});

test('structure confirmation enforces reviewer and topic creation permissions', function () {
    [$document, $user, $payload] = structureFixture();
    $denied = User::factory()->create(['permissions' => []]);
    $this->actingAs($denied)->post(route('curriculum.confirm-structure', $document), $payload)->assertForbidden();
    $user->update(['permissions' => [User::PERMISSION_REVIEW_CURRICULUM]]);
    $this->actingAs($user)->post(route('curriculum.confirm-structure', $document), $payload)->assertSessionHasErrors('topic_id');
    expect(Topic::query()->count())->toBe(0);
});

test('stale document versions concurrent reviews and unapproved documents cannot be confirmed', function (string $change) {
    [$document, $user, $payload] = structureFixture();
    match ($change) {
        'version' => $document->update(['active_chunk_version' => 2]),
        'approval' => $document->update(['approval_status' => 'pending']),
        'embedding' => $document->update(['embedding_status' => 'failed']),
        'review' => $document->curriculumMappings()->first()->update(['status' => 'rejected']),
    };
    $this->actingAs($user)->post(route('curriculum.confirm-structure', $document), $payload)->assertSessionHasErrors('structure');
    expect(Topic::query()->count())->toBe(0);
})->with(['version', 'approval', 'embedding', 'review']);

test('rejected suggestions and cross document mapping ids are never included in bulk approval', function (bool $rejected) {
    [$document, $user, $payload] = structureFixture();
    $mapping = $document->curriculumMappings()->findOrFail($payload['children'][0]['mapping_ids'][0]);
    if ($rejected) {
        $mapping->update(['status' => 'rejected']);
    } else {
        $mapping->update(['knowledge_document_id' => KnowledgeDocument::factory()->create()->id]);
    }
    $payload['review_token'] = app(CurriculumStructureService::class)->reviewToken($document);
    $this->actingAs($user)->post(route('curriculum.confirm-structure', $document), $payload)->assertSessionHasErrors();
    expect(Topic::query()->count())->toBe(0)->and($mapping->refresh()->status)->toBe($rejected ? 'rejected' : 'suggested');
})->with([true, false]);

test('duplicate sibling names are rejected rather than silently replacing evidence', function () {
    [$document, $user, $payload] = structureFixture();
    $payload['children'][1]['name'] = $payload['children'][0]['name'];
    $this->actingAs($user)->post(route('curriculum.confirm-structure', $document), $payload)->assertSessionHasErrors('structure');
    expect(Topic::query()->count())->toBe(0);
});

test('existing topics and subtopics can be confirmed without separate creation permission', function () {
    [$document, $user, $payload] = structureFixture();
    $topic = Topic::factory()->create(['subject_id' => $payload['subject_id'], 'class_level' => '11', 'name' => $payload['topic']['name']]);
    foreach ($payload['children'] as $child) {
        Subtopic::factory()->for($topic)->create(['name' => $child['name'], 'normalized_name' => app(CurriculumMappingService::class)->normalizedName($child['name'])]);
    }
    $user->update(['permissions' => [User::PERMISSION_REVIEW_CURRICULUM]]);
    $this->actingAs($user)->post(route('curriculum.confirm-structure', $document), $payload)->assertSessionHasNoErrors();
    expect(Topic::query()->count())->toBe(1)->and(Subtopic::query()->count())->toBe(2);
});

/** @return array{KnowledgeDocument, User, array<string, mixed>, KnowledgeDocumentChunk} */
function structureFixture(): array
{
    $subject = Subject::factory()->create(['name' => 'Mathematics', 'level' => 'ss']);
    $user = User::factory()->create(['permissions' => [User::PERMISSION_REVIEW_CURRICULUM, User::PERMISSION_CREATE_QUESTIONS, User::PERMISSION_GENERATE_QUESTIONS]]);
    $document = KnowledgeDocument::factory()->create(['title' => 'SS2 Mathematics Quadratic Equations', 'processing_status' => 'completed', 'approval_status' => 'approved', 'embedding_status' => 'completed',
        'embedding_provider' => config('knowledge.embeddings.provider'), 'embedding_model' => config('knowledge.embeddings.model'), 'embedding_dimensions' => config('knowledge.embeddings.dimensions')]);
    $content = 'Quadratic equations have degree two. Factorization uses linear factors. Completing the square adds a constant.';
    $chunk = KnowledgeDocumentChunk::factory()->create(['knowledge_document_id' => $document->id, 'content' => $content, 'content_hash' => hash('sha256', $content)]);
    $rows = [];
    foreach (['Quadratic Equations', 'Factorization', 'Completing the square'] as $name) {
        $mapping = CurriculumTopicMapping::factory()->create(['knowledge_document_id' => $document->id, 'topic_id' => null, 'subject_id' => $subject->id, 'class_level' => '11', 'detected_topic' => $name]);
        $mapping->evidence()->create(['knowledge_document_chunk_id' => $chunk->id, 'content_hash' => $chunk->content_hash, 'excerpt' => $content]);
        $rows[] = ['name' => $name, 'mapping_ids' => [$mapping->id], 'evidence' => [['chunk_id' => $chunk->id, 'excerpt' => $content]]];
    }
    $payload = ['subject_id' => $subject->id, 'class_level' => '11', 'chunk_version' => 1,
        'review_token' => app(CurriculumStructureService::class)->reviewToken($document), 'topic' => $rows[0],
        'children' => [[...$rows[1], 'classification' => 'subtopic'], [...$rows[2], 'classification' => 'subtopic']]];

    return [$document, $user, $payload, $chunk];
}
