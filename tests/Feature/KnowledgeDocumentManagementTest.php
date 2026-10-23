<?php

use App\Jobs\ProcessKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('users need knowledge base permission to access document endpoints', function () {
    $user = User::factory()->create();
    $document = KnowledgeDocument::factory()->for($user, 'uploader')->create();

    $this->actingAs($user)
        ->get(route('knowledge-documents.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('knowledge-documents.show', $document))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('knowledge-documents.store'), [])
        ->assertForbidden();
});

test('authorized users can list and view knowledge documents', function () {
    $user = User::factory()->create([
        'permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE],
    ]);
    $document = KnowledgeDocument::factory()->for($user, 'uploader')->create([
        'title' => 'Junior Secondary Mathematics Curriculum',
    ]);
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create([
        'sequence' => 1,
        'metadata' => ['page' => 4],
    ]);

    $this->actingAs($user)
        ->get(route('knowledge-documents.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('KnowledgeBase/Index')
            ->has('documents.data', 1)
            ->where('documents.data.0.title', 'Junior Secondary Mathematics Curriculum'));

    $this->actingAs($user)
        ->get(route('knowledge-documents.show', $document))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('KnowledgeBase/Show')
            ->where('document.id', $document->id)
            ->where('document.chunks_count', 1)
            ->has('chunks.data', 1)
            ->where('chunks.data.0.metadata.page', 4));

    $chunk = $document->chunks()->first();

    expect($chunk?->metadata)->toBe(['page' => 4])
        ->and($chunk?->sequence)->toBe(1);
});

test('authorized users can upload a document to private storage', function () {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->create([
        'permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE],
    ]);
    $file = UploadedFile::fake()->createWithContent('mathematics-curriculum.pdf', '%PDF-1.7 curriculum content');

    $response = $this->actingAs($user)->post(route('knowledge-documents.store'), [
        'title' => 'Mathematics Curriculum',
        'document_type' => KnowledgeDocument::TYPE_CURRICULUM,
        'description' => 'Approved curriculum source for secondary mathematics.',
        'file' => $file,
    ]);

    $document = KnowledgeDocument::query()->sole();

    $response->assertRedirect(route('knowledge-documents.show', $document));
    Storage::disk('local')->assertExists($document->path);
    Queue::assertPushed(ProcessKnowledgeDocumentJob::class, fn (ProcessKnowledgeDocumentJob $job) => $job->documentId === $document->id);

    expect($document)
        ->disk->toBe('local')
        ->original_name->toBe('mathematics-curriculum.pdf')
        ->processing_status->toBe(KnowledgeDocument::PROCESSING_PENDING)
        ->approval_status->toBe(KnowledgeDocument::APPROVAL_PENDING)
        ->uploaded_by->toBe($user->id)
        ->and($document->checksum)->toHaveLength(64);

    $this->assertDatabaseHas('knowledge_documents', [
        'id' => $document->id,
        'uploaded_by' => $user->id,
        'processing_status' => 'pending',
        'approval_status' => 'pending',
    ]);
});

test('document uploads validate required fields and supported types', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE],
    ]);

    $this->actingAs($user)
        ->from(route('knowledge-documents.index'))
        ->post(route('knowledge-documents.store'), [
            'title' => '',
            'document_type' => 'spreadsheet',
            'file' => UploadedFile::fake()->create('curriculum.exe', 10, 'application/x-msdownload'),
        ])
        ->assertRedirect(route('knowledge-documents.index'))
        ->assertSessionHasErrors(['title', 'document_type', 'file']);

    expect(KnowledgeDocument::query()->count())->toBe(0);
});

test('document uploads cannot exceed twenty five megabytes', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE],
    ]);

    $this->actingAs($user)
        ->post(route('knowledge-documents.store'), [
            'title' => 'Oversized Textbook',
            'document_type' => KnowledgeDocument::TYPE_TEXTBOOK,
            'file' => UploadedFile::fake()->create('textbook.pdf', 25 * 1024 + 1, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['file']);

    expect(KnowledgeDocument::query()->count())->toBe(0);
});
