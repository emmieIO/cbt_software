<?php

namespace App\Services;

use App\Jobs\EmbedKnowledgeDocumentJob;
use App\Jobs\IdentifyCurriculumTopicsJob;
use App\Jobs\ProcessKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class KnowledgeDocumentService
{
    /** @param array{title: string, document_type: string, description: string|null} $data */
    public function create(array $data, UploadedFile $file, User $uploader): KnowledgeDocument
    {
        $disk = 'local';
        $checksum = hash_file('sha256', $file->getRealPath());

        if ($checksum === false) {
            throw new RuntimeException('The document checksum could not be calculated.');
        }

        $path = $file->store('knowledge-documents/'.$uploader->id, $disk);

        if ($path === false) {
            throw new RuntimeException('The document could not be stored.');
        }

        try {
            $document = DB::transaction(fn (): KnowledgeDocument => KnowledgeDocument::query()->create([
                ...$data,
                'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 255, ''),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => strtolower($file->extension() ?: $file->getClientOriginalExtension()),
                'size_bytes' => $file->getSize(),
                'checksum' => $checksum,
                'uploaded_by' => $uploader->id,
            ]));

        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        $this->queueProcessing($document);

        return $document;
    }

    public function queueProcessing(KnowledgeDocument $document, bool $reprocess = false): bool
    {
        try {
            app(KnowledgeJobDispatcher::class)->dispatch(new ProcessKnowledgeDocumentJob($document->id, $reprocess));
        } catch (Throwable $exception) {
            $status = $reprocess ? 'reprocessing_status' : 'processing_status';
            KnowledgeDocument::query()->whereKey($document->id)
                ->whereIn($status, ['pending', 'processing'])
                ->update([
                    $status => 'failed',
                    $reprocess ? 'reprocessing_error' : 'processing_error' => 'Document processing could not be queued. Check the queue service and retry.',
                ]);
            report($exception);

            return false;
        }

        return true;
    }

    public function queueEmbedding(KnowledgeDocument $document, ?int $chunkVersion = null): bool
    {
        $chunkVersion ??= $document->active_chunk_version;
        $replacement = $chunkVersion === $document->pending_chunk_version && $chunkVersion !== $document->active_chunk_version;
        try {
            app(KnowledgeJobDispatcher::class)->dispatch(new EmbedKnowledgeDocumentJob($document->id, $chunkVersion));
        } catch (Throwable $exception) {
            KnowledgeDocument::query()->whereKey($document->id)
                ->where($replacement ? 'pending_chunk_version' : 'active_chunk_version', $chunkVersion)
                ->whereIn($replacement ? 'reprocessing_status' : 'embedding_status', ['pending', 'embedding', 'processing'])
                ->update([
                    $replacement ? 'reprocessing_status' : 'embedding_status' => 'failed',
                    $replacement ? 'reprocessing_error' : 'embedding_error' => 'Document embedding could not be queued. Check the queue service and retry.',
                ]);
            report($exception);

            return false;
        }

        return true;
    }

    public function queueTopicDetection(KnowledgeDocument $document): bool
    {
        try {
            app(KnowledgeJobDispatcher::class)->dispatch(new IdentifyCurriculumTopicsJob($document->id, $document->active_chunk_version));
        } catch (Throwable $exception) {
            KnowledgeDocument::query()->whereKey($document->id)
                ->where('active_chunk_version', $document->active_chunk_version)
                ->whereIn('topic_detection_status', ['pending', 'queued'])
                ->update([
                    'topic_detection_status' => 'failed',
                    'topic_detection_error' => 'Topic identification could not be queued. Check the queue service and retry.',
                ]);

            report($exception);

            return false;
        }

        return true;
    }
}
