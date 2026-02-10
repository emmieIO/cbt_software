<?php

namespace App\Jobs;

use App\Ai\Agents\QuestionGeneratorAgent;
use App\DTOs\OptionDTO;
use App\DTOs\QuestionDTO;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\AiQuestionsSeeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateQuestionsJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $userId,
        protected string $subjectId,
        protected string $topicId,
        protected string $schoolClassId,
        protected int $count,
        protected string $difficulty
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);
        $subject = Subject::find($this->subjectId);
        $topic = Topic::find($this->topicId);
        $schoolClass = SchoolClass::find($this->schoolClassId);

        if (! $user || ! $subject || ! $topic || ! $schoolClass) {
            return;
        }

        // Set tracking flag in cache (valid for 15 mins max as a safety)
        $cacheKey = "user_{$this->userId}_seeding_status";
        Cache::put($cacheKey, [
            'topic' => $topic->name,
            'started_at' => now()->timestamp,
        ], now()->addMinutes(15));

        Log::info('AI Question Generation Started (Laravel AI SDK)', [
            'topic' => $topic->name,
            'class' => $schoolClass->name,
            'requested_count' => $this->count,
        ]);

        $agent = new QuestionGeneratorAgent;

        $prompt = "Generate exactly {$this->count} high-quality questions for Chrisland Schools.\n\n".
                  "CONTEXT:\n".
                  "- Subject: {$subject->name}\n".
                  "- Topic: {$topic->name}\n".
                  "- Class Level: {$schoolClass->name}\n".
                  "- Target Difficulty: {$this->difficulty}";

        try {
            $response = $agent->prompt($prompt);

            $questions = $response['questions'] ?? [];

            if (empty($questions)) {
                throw new \Exception('AI returned no questions.');
            }

            // Map AI output to DTOs
            $dtos = array_map(function ($q) {
                return new QuestionDTO(
                    topic_id: $this->topicId,
                    school_class_id: $this->schoolClassId,
                    content: $q['content'],
                    explanation: $q['explanation'],
                    type: $q['type'],
                    difficulty: $q['difficulty'],
                    options: array_map(
                        fn ($o) => new OptionDTO(content: $o['content'], is_correct: $o['is_correct']),
                        $q['options']
                    )
                );
            }, $questions);

            // Find a staff user to associate with the creation (consistent with previous logic)
            $staff = User::role('staff')->first();
            $creatorId = $staff ? $staff->id : $this->userId;

            app(\App\Services\QuestionService::class)->createQuestionsBatch($dtos, $creatorId);

            $seededCount = count($dtos);

            Log::info('AI Question Generation Finished', [
                'seeded_count' => $seededCount,
                'requested_count' => $this->count,
            ]);

            $user->notify(new AiQuestionsSeeded(
                $subject->name,
                $topic->name,
                $seededCount
            ));

        } catch (\Throwable $e) {
            Log::error('AI Question Generation Failed', [
                'error' => $e->getMessage(),
                'topic' => $topic->name,
            ]);

            $user->notify(new AiQuestionsSeeded(
                $subject->name,
                $topic->name,
                0
            ));
        } finally {
            Cache::forget($cacheKey);
        }
    }
}
