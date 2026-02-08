<?php

namespace App\Jobs;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Neuron\Questions\QuestionSeederAgent;
use App\Notifications\AiQuestionsSeeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use NeuronAI\Chat\Messages\UserMessage;

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
        \Illuminate\Support\Facades\Cache::put($cacheKey, [
            'topic' => $topic->name,
            'started_at' => now()->timestamp,
        ], now()->addMinutes(15));

        \Illuminate\Support\Facades\Log::info("AI Question Generation Started", [
            'topic' => $topic->name,
            'class' => $schoolClass->name,
            'requested_count' => $this->count
        ]);

        $startingCount = \App\Models\Question::where('topic_id', $this->topicId)
            ->where('school_class_id', $this->schoolClassId)
            ->count();

        $agent = new QuestionSeederAgent;

        $prompt = "Generate and seed exactly {$this->count} high-quality questions for Chrisland Schools.\n\n".
                  "CONTEXT:\n".
                  "- Subject: {$subject->name}\n".
                  "- Topic: {$topic->name} (topic_id: {$this->topicId})\n".
                  "- Class Level: {$schoolClass->name} (school_class_id: {$this->schoolClassId})\n".
                  "- Target Difficulty: {$this->difficulty}\n\n".
                  "STRICT INSTRUCTIONS:\n".
                  "1. You MUST use the 'seed_question_batch' tool to save the questions.\n".
                  "2. Do NOT provide the questions in your text response; only use the tool.\n".
                  "3. Ensure all distractors are plausible.\n".
                  "4. Each question MUST have exactly 4 options (for MCQs).\n".
                  "5. Double-check that the topic_id and school_class_id are correct in your tool call.";

        try {
            $agent->chat(new UserMessage($prompt));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("AI Question Generation Failed", [
                'error' => $e->getMessage(),
                'topic' => $topic->name
            ]);
        } finally {
            // Always clear the status when done
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        $endingCount = \App\Models\Question::where('topic_id', $this->topicId)
            ->where('school_class_id', $this->schoolClassId)
            ->count();

        $seededCount = $endingCount - $startingCount;

        \Illuminate\Support\Facades\Log::info("AI Question Generation Finished", [
            'seeded_count' => $seededCount,
            'requested_count' => $this->count
        ]);

        if ($seededCount === 0) {
            $user->notify(new AiQuestionsSeeded(
                $subject->name,
                $topic->name,
                0
            ));
            return;
        }

        $user->notify(new AiQuestionsSeeded(
            $subject->name,
            $topic->name,
            $seededCount
        ));
    }
}
