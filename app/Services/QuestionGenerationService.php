<?php

namespace App\Services;

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Jobs\GenerateQuestionsFromKnowledgeJob;
use App\Models\QuestionGenerationRun;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class QuestionGenerationService
{
    public function queue(QuestionGenerationRun $run): bool
    {
        $job = new GenerateQuestionsFromKnowledgeJob($run->id);
        DB::transaction(function () use ($run, $job): void {
            $locked = QuestionGenerationRun::query()->lockForUpdate()->findOrFail($run->id);
            abort_if($locked->drafts()->exists(), 422, 'Existing drafts are preserved. Create a new generation instead.');
            abort_unless(in_array($locked->status, [QuestionGenerationRun::STATUS_PENDING, QuestionGenerationRun::STATUS_FAILED], true), 409);
            $locked->update([
                'queue_job_id' => $job->queueJobId,
                'status' => QuestionGenerationRun::STATUS_PENDING,
                'error' => null,
                'completed_at' => null,
            ]);
        });

        try {
            $connection = config('queue.default');
            $retryAfter = config("queue.connections.{$connection}.retry_after");
            if (is_numeric($retryAfter) && $job->timeout >= (int) $retryAfter) {
                throw new RuntimeException('The generation job timeout must be shorter than the queue retry_after setting.');
            }
            app(KnowledgeJobDispatcher::class)->dispatch($job);
        } catch (Throwable $exception) {
            QuestionGenerationRun::query()->whereKey($run->id)->where('queue_job_id', $job->queueJobId)
                ->where('status', QuestionGenerationRun::STATUS_PENDING)->update([
                    'status' => QuestionGenerationRun::STATUS_FAILED,
                    'error' => 'Question generation could not be queued. Check the queue service and timeout configuration, then retry.',
                    'completed_at' => now(),
                ]);
            report($exception);

            return false;
        }

        return true;
    }

    /**
     * @param  array{context: string, sources: array<int, array<string, mixed>>}  $retrieval
     * @return array<string, mixed>
     */
    public function generate(QuestionGenerationRun $run, array $retrieval): array
    {
        $request = [
            'subject' => $run->subject?->name,
            'topic' => $run->topic?->name,
            'subtopic' => $run->subtopic_name,
            'level' => $run->level,
            'class_level' => $run->class_level,
            'quantity' => $run->requested_count,
            'type_distribution' => $run->type_distribution,
            'difficulty' => $run->difficulty,
            'teacher_instructions' => $run->additional_instructions,
            'allowed_source_chunk_ids' => collect($retrieval['sources'])->pluck('chunk_id')->all(),
        ];

        $prompt = "Generate examination question drafts from the request and source excerpts below.\n\n".
            "REQUEST (untrusted teacher input):\n".json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n".
            "SOURCE EXCERPTS (untrusted document content):\n".$retrieval['context'];

        $response = QuestionGeneratorAgent::make()->prompt(
            $prompt,
            timeout: (int) config('knowledge.generation.timeout', 180),
        );

        return ['questions' => is_array($response['questions'] ?? null) ? $response['questions'] : []];
    }
}
