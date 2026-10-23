<?php

namespace App\Http\Controllers;

use App\Http\Requests\KnowledgeDocuments\SearchKnowledgeDocumentsRequest;
use App\Services\KnowledgeRetrievalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeSearchController extends Controller
{
    public function __construct(private readonly KnowledgeRetrievalService $knowledgeRetrievalService) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->canManageKnowledgeBase(), 403);

        return Inertia::render('KnowledgeBase/Search', [
            'query' => '',
            'results' => [],
            'retrieval' => [
                'candidate_count' => (int) config('knowledge.retrieval.candidate_count'),
                'similarity_threshold' => (float) config('knowledge.retrieval.similarity_threshold'),
            ],
        ]);
    }

    public function search(SearchKnowledgeDocumentsRequest $request): Response
    {
        $query = $request->string('query')->trim()->toString();
        $results = $this->knowledgeRetrievalService->search($query);

        return Inertia::render('KnowledgeBase/Search', [
            'query' => $query,
            'results' => $results->map(fn ($chunk): array => [
                'id' => $chunk->id,
                'sequence' => $chunk->sequence,
                'content' => $chunk->content,
                'metadata' => $chunk->metadata,
                'similarity' => round((float) $chunk->getAttribute('similarity'), 6),
                'distance' => round((float) $chunk->getAttribute('distance'), 6),
                'document' => $chunk->document,
            ])->values(),
            'retrieval' => [
                'candidate_count' => (int) config('knowledge.retrieval.candidate_count'),
                'similarity_threshold' => (float) config('knowledge.retrieval.similarity_threshold'),
            ],
        ]);
    }
}
