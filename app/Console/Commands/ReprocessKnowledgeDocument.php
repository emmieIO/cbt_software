<?php

namespace App\Console\Commands;

use App\Models\KnowledgeDocument;
use App\Services\KnowledgeDocumentService;
use Illuminate\Console\Command;

class ReprocessKnowledgeDocument extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:reprocess {document : Knowledge document ULID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue safe text extraction, chunking, and embedding replacement for a knowledge document';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $document = KnowledgeDocument::query()->find($this->argument('document'));

        if ($document === null) {
            $this->error('Knowledge document not found.');

            return self::FAILURE;
        }

        if ($document->processing_status !== KnowledgeDocument::PROCESSING_COMPLETED) {
            $this->error('Only completed knowledge documents can be safely reprocessed.');

            return self::FAILURE;
        }

        if (in_array($document->reprocessing_status, [
            KnowledgeDocument::REPROCESSING_PROCESSING,
            KnowledgeDocument::REPROCESSING_EMBEDDING,
        ], true)) {
            $this->error('This knowledge document is already being reprocessed.');

            return self::FAILURE;
        }

        $document->update([
            'reprocessing_status' => KnowledgeDocument::REPROCESSING_PROCESSING,
            'reprocessing_error' => null,
        ]);

        if (! app(KnowledgeDocumentService::class)->queueProcessing($document, true)) {
            $this->error('Reprocessing could not be queued. The active index is unchanged; check the queue service and retry.');

            return self::FAILURE;
        }
        $this->info("Queued knowledge document {$document->id} for safe reprocessing.");

        return self::SUCCESS;
    }
}
