<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\CurriculumTopicIdentificationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class IdentifyCurriculumTopicsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public int $tries = 3;

    public int $timeout = 420;

    public int $uniqueFor = 1800;

    public array $backoff = [30, 120, 300];

    public function __construct(public string $documentId, public int $chunkVersion)
    {
        $this->onQueue('ai');
    }

    /**
     * Execute the job.
     */
    public function uniqueId(): string
    {
        return $this->documentId.':'.$this->chunkVersion;
    }

    public function handle(CurriculumTopicIdentificationService $service): void
    {
        $document = KnowledgeDocument::query()->find($this->documentId);
        if (! $document || $document->active_chunk_version !== $this->chunkVersion
            || $document->approval_status !== KnowledgeDocument::APPROVAL_APPROVED) {
            return;
        }
        if ($document->topic_detection_version === $this->chunkVersion && $document->topic_detection_status === 'completed') {
            return;
        }
        try {
            $service->identify($document, $this->chunkVersion);
        } catch (Throwable $exception) {
            $this->failed($exception);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        KnowledgeDocument::query()->whereKey($this->documentId)->where('active_chunk_version', $this->chunkVersion)->update([
            'topic_detection_status' => 'failed',
            'topic_detection_error' => 'Topic identification failed. Check the AI provider configuration and retry; completed batches have been retained.',
        ]);
        if ($exception) {
            report($exception);
        }
    }
}
