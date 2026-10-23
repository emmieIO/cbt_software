<?php

namespace App\Http\Controllers;

use App\Http\Requests\KnowledgeDocuments\StoreKnowledgeDocumentRequest;
use App\Models\KnowledgeDocument;
use App\Models\Subject;
use App\Services\CurriculumStructureService;
use App\Services\KnowledgeDocumentService;
use App\Support\AcademicLevels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeDocumentController extends Controller
{
    public function __construct(private readonly KnowledgeDocumentService $knowledgeDocumentService) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->canAccessKnowledgeBase(), 403);

        $filters = $request->only(['search', 'document_type', 'processing_status', 'approval_status', 'embedding_status']);

        return Inertia::render('KnowledgeBase/Index', [
            'documents' => KnowledgeDocument::query()
                ->with('uploader:id,name')
                ->withCount(['chunks' => fn ($query) => $query
                    ->whereColumn('knowledge_document_chunks.chunk_version', 'knowledge_documents.active_chunk_version')])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query
                    ->where(fn ($documentQuery) => $documentQuery
                        ->whereLike('title', '%'.$search.'%')
                        ->orWhereLike('original_name', '%'.$search.'%')))
                ->when($filters['document_type'] ?? null, fn ($query, $type) => $query->where('document_type', $type))
                ->when($filters['processing_status'] ?? null, fn ($query, $status) => $query->where('processing_status', $status))
                ->when($filters['approval_status'] ?? null, fn ($query, $status) => $query->where('approval_status', $status))
                ->when($filters['embedding_status'] ?? null, fn ($query, $status) => $query->where('embedding_status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'documentTypes' => KnowledgeDocument::DOCUMENT_TYPES,
        ]);
    }

    public function store(StoreKnowledgeDocumentRequest $request): RedirectResponse
    {
        $document = $this->knowledgeDocumentService->create(
            $request->payload(),
            $request->uploadedFile(),
            $request->user(),
        );

        return to_route('knowledge-documents.show', $document)
            ->with('success', 'Document uploaded successfully.');
    }

    public function show(Request $request, KnowledgeDocument $knowledgeDocument): Response
    {
        abort_unless($request->user()?->canAccessKnowledgeBase(), 403);

        $knowledgeDocument->load(['uploader:id,name', 'approver:id,name']);
        $knowledgeDocument->setAttribute(
            'chunks_count',
            $knowledgeDocument->chunks()->where('chunk_version', $knowledgeDocument->active_chunk_version)->count(),
        );

        return Inertia::render('KnowledgeBase/Show', [
            'document' => $knowledgeDocument,
            'curriculumMappings' => $knowledgeDocument->curriculumMappings()->with(['subject:id,name', 'topic:id,name', 'subtopic:id,name,topic_id', 'reviewer:id,name', 'reviews.reviewer:id,name', 'evidence'])->orderBy('detected_topic')->get(),
            'curriculumReviewToken' => app(CurriculumStructureService::class)->reviewToken($knowledgeDocument),
            'curriculumSubjects' => fn () => Subject::query()->with(['topics:id,subject_id,name,class_level', 'topics.subtopics:id,topic_id,name'])->orderBy('name')->get(['id', 'name', 'level']),
            'curriculumClasses' => AcademicLevels::classOptions(),
            'evidenceChunks' => fn () => $knowledgeDocument->chunks()->where('chunk_version', $knowledgeDocument->active_chunk_version)
                ->orderBy('sequence')->get(['id', 'sequence', 'metadata', 'content'])->map(fn ($chunk) => [
                    'id' => $chunk->id, 'sequence' => $chunk->sequence, 'metadata' => $chunk->metadata, 'excerpt' => mb_substr($chunk->content, 0, 500),
                ]),
            'chunks' => $knowledgeDocument->chunks()
                ->where('chunk_version', $knowledgeDocument->active_chunk_version)
                ->select([
                    'id',
                    'knowledge_document_id',
                    'chunk_version',
                    'sequence',
                    'content',
                    'content_hash',
                    'estimated_token_count',
                    'metadata',
                    'embedding_provider',
                    'embedding_model',
                    'embedding_dimensions',
                    'embedded_at',
                ])
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function retry(Request $request, KnowledgeDocument $knowledgeDocument): RedirectResponse
    {
        abort_unless($request->user()?->canManageKnowledgeBase(), 403);
        abort_unless($knowledgeDocument->processing_status === KnowledgeDocument::PROCESSING_FAILED, 409);

        $knowledgeDocument->update([
            'processing_status' => KnowledgeDocument::PROCESSING_PENDING,
            'processing_error' => null,
        ]);

        if (! $this->knowledgeDocumentService->queueProcessing($knowledgeDocument)) {
            return back()->with('error', 'Document processing could not be queued. Please retry when the queue service is available.');
        }

        return to_route('knowledge-documents.show', $knowledgeDocument)
            ->with('success', 'Document processing has been queued again.');
    }

    public function reprocess(Request $request, KnowledgeDocument $knowledgeDocument): RedirectResponse
    {
        abort_unless($request->user()?->canManageKnowledgeBase(), 403);
        abort_unless($knowledgeDocument->processing_status === KnowledgeDocument::PROCESSING_COMPLETED, 409);
        abort_if(in_array($knowledgeDocument->reprocessing_status, [
            KnowledgeDocument::REPROCESSING_PROCESSING,
            KnowledgeDocument::REPROCESSING_EMBEDDING,
        ], true), 409);

        $knowledgeDocument->update([
            'reprocessing_status' => KnowledgeDocument::REPROCESSING_PROCESSING,
            'reprocessing_error' => null,
        ]);

        if (! $this->knowledgeDocumentService->queueProcessing($knowledgeDocument, true)) {
            return back()->with('error', 'Reprocessing could not be queued. The current search index is unchanged; please retry.');
        }

        return to_route('knowledge-documents.show', $knowledgeDocument)
            ->with('success', 'Document reprocessing has been queued. The current search index remains active until replacement succeeds.');
    }

    public function approve(Request $request, KnowledgeDocument $knowledgeDocument): RedirectResponse
    {
        abort_unless($request->user()?->canApproveKnowledgeDocuments(), 403);

        $document = DB::transaction(function () use ($knowledgeDocument, $request): KnowledgeDocument {
            $document = KnowledgeDocument::query()->lockForUpdate()->findOrFail($knowledgeDocument->id);

            abort_unless($document->processing_status === KnowledgeDocument::PROCESSING_COMPLETED, 409);
            abort_if($document->approval_status === KnowledgeDocument::APPROVAL_APPROVED, 409);
            abort_if($document->chunks()->where('chunk_version', $document->active_chunk_version)->doesntExist(), 409);

            $document->update([
                'approval_status' => KnowledgeDocument::APPROVAL_APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'rejection_reason' => null,
                'embedding_status' => KnowledgeDocument::EMBEDDING_PENDING,
                'embedding_error' => null,
                'embedded_at' => null,
            ]);

            return $document;
        });

        if (! $this->knowledgeDocumentService->queueEmbedding($document)) {
            return back()->with('error', 'Document approval was saved, but embedding could not be queued. Please retry embedding.');
        }

        return to_route('knowledge-documents.show', $knowledgeDocument)
            ->with('success', 'Document approved and embedding has been queued.');
    }

    public function retryEmbedding(Request $request, KnowledgeDocument $knowledgeDocument): RedirectResponse
    {
        abort_unless($request->user()?->canApproveKnowledgeDocuments(), 403);
        abort_unless($knowledgeDocument->approval_status === KnowledgeDocument::APPROVAL_APPROVED, 409);
        abort_unless($knowledgeDocument->processing_status === KnowledgeDocument::PROCESSING_COMPLETED, 409);
        abort_unless($knowledgeDocument->embedding_status === KnowledgeDocument::EMBEDDING_FAILED, 409);

        $knowledgeDocument->update([
            'embedding_status' => KnowledgeDocument::EMBEDDING_PENDING,
            'embedding_error' => null,
            'embedded_at' => null,
        ]);

        if (! $this->knowledgeDocumentService->queueEmbedding($knowledgeDocument->refresh())) {
            return back()->with('error', 'Document embedding could not be queued. Please retry when the queue service is available.');
        }

        return to_route('knowledge-documents.show', $knowledgeDocument)
            ->with('success', 'Document embedding has been queued again.');
    }
}
