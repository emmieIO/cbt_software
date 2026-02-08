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
    public $timeout = 300;

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

        $agent = new QuestionSeederAgent;

        $prompt = "Generate and seed {$this->count} high-quality questions for Chrisland Schools.\n\n".
                  "REQUIRED CONTEXT:\n".
                  "- topic_id: {$this->topicId} (Topic: {$topic->name})\n".
                  "- school_class_id: {$this->schoolClassId} (Class: {$schoolClass->name})\n".
                  "- difficulty: {$this->difficulty}\n\n".
                  "CORE INSTRUCTION:\n".
                  "1. Generate exactly {$this->count} questions.\n".
                  "2. Each question MUST use the provided topic_id and school_class_id.\n".
                  "3. Use the 'seed_question_batch' tool ONCE to save all questions.\n".
                  "4. Pass the questions as an array to the 'questions' parameter.\n".
                  '5. Do NOT provide the questions in your textual response.';

        $agent->chat(new UserMessage($prompt));

        $user->notify(new AiQuestionsSeeded(
            $subject->name,
            $topic->name,
            $this->count
        ));
    }
}
