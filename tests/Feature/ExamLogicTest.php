<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamLogicTest extends TestCase
{
    use RefreshDatabase;

    protected ExamService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExamService::class);
    }

    public function test_biennial_rotation_policy_excludes_recently_used_questions(): void
    {
        $subject = Subject::factory()->create();
        $class = \App\Models\SchoolClass::factory()->create();
        $topic = Topic::factory()->create(['subject_id' => $subject->id]);

        // 1. Create a "used" question (used 1 month ago)
        $usedQuestion = Question::factory()->create([
            'topic_id' => $topic->id,
            'school_class_id' => $class->id,
            'last_used_at' => now()->subMonth(),
        ]);

        // 2. Create a "fresh" question (never used)
        $freshQuestion = Question::factory()->create([
            'topic_id' => $topic->id,
            'school_class_id' => $class->id,
            'last_used_at' => null,
        ]);

        $exam = Exam::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
        ]);

        // Auto-select 1 question
        $this->service->autoSelectQuestions($exam, 1);

        // Should have selected the fresh one, not the one used last month
        $this->assertCount(1, $exam->questions);
        $this->assertEquals($freshQuestion->id, $exam->questions->first()->id);
    }

    public function test_exam_attempt_shuffling_is_deterministic_per_seed(): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->create();
        $questions = Question::factory()->count(10)->create();
        $exam->questions()->attach($questions->pluck('id'));

        // Start attempt
        $attempt = $this->service->startExam($user, $exam);

        $order1 = $attempt->metadata['question_order'];

        // Fetching again should yield same order (locked-in)
        $attemptQuestions = $this->service->getAttemptQuestions($attempt);
        $order2 = $attemptQuestions->pluck('id')->toArray();

        $this->assertEquals($order1, $order2);
    }
}
