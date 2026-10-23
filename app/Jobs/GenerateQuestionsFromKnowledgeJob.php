<?php

namespace App\Jobs;

use App\Models\QuestionGenerationRun;
use App\Services\GeneratedQuestionValidator;
use App\Services\QuestionGenerationContextService;
use App\Services\QuestionGenerationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class GenerateQuestionsFromKnowledgeJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 240;

    public int $uniqueFor = 7200;

    public bool $failOnTimeout = true;

    public ?string $queueJobId = null;

    private int $attemptVersion = 0;

    /** @var array<int, int> */
    public array $backoff = [15, 60, 180];

    public function __construct(public readonly string $runId)
    {
        $this->queueJobId = (string) Str::uuid();
        $this->timeout = max(240, (int) config('knowledge.embeddings.timeout', 120)
            + (int) config('knowledge.generation.timeout', 180) + 60);
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return $this->runId.':'.$this->queueJobId;
    }

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->runId))->releaseAfter(60)->expireAfter($this->timeout + 60)];
    }

    public function handle(
        QuestionGenerationContextService $contextService,
        QuestionGenerationService $generationService,
        GeneratedQuestionValidator $validator,
    ): void {
        $run = QuestionGenerationRun::query()->with(['subject', 'topic', 'drafts'])->findOrFail($this->runId);
        $this->queueJobId ??= $this->job?->uuid() ?? (string) Str::uuid();

        $canStart = DB::transaction(function () use ($run): bool {
            $locked = QuestionGenerationRun::query()->lockForUpdate()->findOrFail($run->id);
            if ($locked->drafts()->exists() || ($locked->queue_job_id !== null && $locked->queue_job_id !== $this->queueJobId)) {
                return false;
            }
            $this->attemptVersion = $locked->generation_attempt + 1;
            $locked->update(['queue_job_id' => $this->queueJobId, 'generation_attempt' => $this->attemptVersion, 'status' => QuestionGenerationRun::STATUS_RETRIEVING]);

            return true;
        });
        if (! $canStart) {
            return;
        }

        try {
            if (! QuestionGenerationRun::query()->whereKey($run->id)->where('queue_job_id', $this->queueJobId)->where('generation_attempt', $this->attemptVersion)->update([
                'status' => QuestionGenerationRun::STATUS_RETRIEVING,
                'started_at' => $run->started_at ?? now(),
                'completed_at' => null,
                'error' => null,
                'validation_errors' => null,
                'warnings' => null,
            ])) {
                return;
            }

            $retrieval = $contextService->build($run);
            if (! QuestionGenerationRun::query()->whereKey($run->id)->where('queue_job_id', $this->queueJobId)->where('generation_attempt', $this->attemptVersion)->update([
                'status' => QuestionGenerationRun::STATUS_GENERATING,
                'retrieval_query' => $retrieval['query'],
                'source_references' => json_encode($retrieval['sources'], JSON_THROW_ON_ERROR),
                'provider' => config('knowledge.generation.provider', 'deepseek'),
                'model' => config('knowledge.generation.model', 'deepseek-chat'),
            ])) {
                return;
            }
            $run->refresh();

            $response = $generationService->generate($run, $retrieval);
            $result = $validator->validate($run, $response['questions'], $retrieval['sources']);
            $validCount = collect($result['drafts'])->where('is_valid', true)->count();

            $status = match (true) {
                $result['drafts'] === [], $validCount === 0 => QuestionGenerationRun::STATUS_FAILED,
                $validCount !== $run->requested_count || $result['errors'] !== [] => QuestionGenerationRun::STATUS_PARTIAL,
                default => QuestionGenerationRun::STATUS_COMPLETED,
            };

            DB::transaction(function () use ($run, $result, $status): void {
                $locked = QuestionGenerationRun::query()->lockForUpdate()->findOrFail($run->id);
                if ($locked->queue_job_id !== $this->queueJobId || $locked->generation_attempt !== $this->attemptVersion || $locked->drafts()->exists()) {
                    return;
                }
                foreach ($result['drafts'] as $draft) {
                    $run->drafts()->create($draft);
                }

                $run->update([
                    'status' => $status,
                    'validation_errors' => $result['errors'] ?: null,
                    'warnings' => $result['warnings'] ?: null,
                    'error' => $status === QuestionGenerationRun::STATUS_FAILED
                        ? 'The generator did not return any valid question drafts.'
                        : null,
                    'completed_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            QuestionGenerationRun::query()->whereKey($run->id)->where('queue_job_id', $this->queueJobId)->where('generation_attempt', $this->attemptVersion)->whereDoesntHave('drafts')->update([
                'status' => QuestionGenerationRun::STATUS_FAILED,
                'error' => $this->safeError($exception),
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $jobId = $this->queueJobId ?? $this->job?->uuid();
        if ($jobId === null) {
            return;
        }
        QuestionGenerationRun::query()->whereKey($this->runId)->where('queue_job_id', $jobId)
            ->whereIn('status', [QuestionGenerationRun::STATUS_PENDING, QuestionGenerationRun::STATUS_RETRIEVING, QuestionGenerationRun::STATUS_GENERATING, QuestionGenerationRun::STATUS_FAILED])
            ->whereDoesntHave('drafts')->update([
                'status' => QuestionGenerationRun::STATUS_FAILED,
                'error' => $exception ? $this->safeError($exception) : 'Question generation failed after several attempts.',
                'completed_at' => now(),
            ]);
    }

    private function safeError(Throwable $exception): string
    {
        if ($exception instanceof TimeoutExceededException) {
            return 'Question generation exceeded its time limit. Please retry, or request fewer questions.';
        }
        if ($exception instanceof RequestException || $exception instanceof ConnectionException) {
            return 'The AI provider request failed. Check the server logs and provider configuration before retrying.';
        }
        $message = trim($exception->getMessage());

        return mb_substr($message !== '' ? $message : 'Question generation failed.', 0, 1000);
    }
}
