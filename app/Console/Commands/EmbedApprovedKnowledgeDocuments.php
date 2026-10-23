<?php

namespace App\Console\Commands;

use App\Models\KnowledgeDocument;
use App\Services\DocumentEmbeddingService;
use App\Services\KnowledgeDocumentService;
use Illuminate\Console\Command;

class EmbedApprovedKnowledgeDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:embed-approved';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue embedding generation for approved, processed knowledge documents';

    /**
     * Execute the console command.
     */
    public function handle(DocumentEmbeddingService $documentEmbeddingService): int
    {
        $configuration = $documentEmbeddingService->configuration();
        $query = KnowledgeDocument::query()
            ->where('processing_status', KnowledgeDocument::PROCESSING_COMPLETED)
            ->where('approval_status', KnowledgeDocument::APPROVAL_APPROVED)
            ->where(function ($query) use ($configuration): void {
                $query->where('embedding_status', '!=', KnowledgeDocument::EMBEDDING_COMPLETED)
                    ->orWhereNull('embedding_provider')
                    ->orWhere('embedding_provider', '!=', $configuration['provider'])
                    ->orWhereNull('embedding_model')
                    ->orWhere('embedding_model', '!=', $configuration['model'])
                    ->orWhereNull('embedding_dimensions')
                    ->orWhere('embedding_dimensions', '!=', $configuration['dimensions']);
            });
        $documentCount = 0;
        $failedCount = 0;

        $query->eachById(function (KnowledgeDocument $document) use (&$documentCount, &$failedCount): void {
            $document->update([
                'embedding_status' => KnowledgeDocument::EMBEDDING_PENDING,
                'embedding_error' => null,
                'embedded_at' => null,
            ]);

            if (app(KnowledgeDocumentService::class)->queueEmbedding($document)) {
                $documentCount++;
            } else {
                $failedCount++;
            }
        });

        $this->info("Queued {$documentCount} approved knowledge document(s) for embedding.");

        if ($failedCount > 0) {
            $this->error("{$failedCount} document(s) could not be queued. Check the queue service and retry.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
