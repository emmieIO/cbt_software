<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\Option;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\ExamService;
use Exception;
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
        $class = SchoolClass::factory()->create();
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
        /** @var Question $selectedQuestion */
        $selectedQuestion = $exam->questions()->firstOrFail();

        // Should have selected the fresh one, not the one used last month
        $this->assertCount(1, $exam->questions);
        $this->assertEquals($freshQuestion->id, $selectedQuestion->id);
    }

    public function test_exam_attempt_shuffling_is_deterministic_per_seed(): void
    {
        $class = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $user = User::factory()->create(['school_class_id' => $class->id]);
        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $questions = Question::factory()->count(10)->create(['school_class_id' => $class->id]);
        $exam->questions()->attach($questions->pluck('id'));

        // Start attempt
        $attempt = $this->service->startExam($user, $exam);

        $order1 = $attempt->metadata['question_order'];

        // Fetching again should yield same order (locked-in)
        $attemptQuestions = $this->service->getAttemptQuestions($attempt);
        $order2 = $attemptQuestions->pluck('id')->toArray();

        $this->assertEquals($order1, $order2);
    }

    public function test_cannot_start_new_attempt_after_submission(): void
    {
        $class = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $user = User::factory()->create(['school_class_id' => $class->id]);
        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $question = Question::factory()->create(['school_class_id' => $class->id]);
        $exam->questions()->attach($question->id);

        $attempt = $this->service->startExam($user, $exam);
        $attempt->update(['status' => AttemptStatus::SUBMITTED, 'submitted_at' => now()]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only one attempt is permitted');

        $this->service->startExam($user, $exam);
    }

    public function test_submit_attempt_is_idempotent_when_already_submitted(): void
    {
        $class = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $user = User::factory()->create(['school_class_id' => $class->id]);
        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $question = Question::factory()->create(['school_class_id' => $class->id]);
        $correctOption = Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => false]);
        $exam->questions()->attach($question->id);

        $attempt = $this->service->startExam($user, $exam);

        $this->service->submitAttempt(
            $attempt,
            [$question->id => $correctOption->id],
            ['termination_reason' => 'user_submit'],
            ['tab_switches' => 0]
        );

        $firstAttempt = $attempt->fresh();
        $firstScore = $firstAttempt->score;
        $firstAnswersCount = ExamAnswer::query()->where('exam_attempt_id', $attempt->id)->count();

        $this->service->submitAttempt(
            $firstAttempt,
            [$question->id => 'non-existent-option'],
            ['termination_reason' => 'timeout'],
            ['tab_switches' => 5]
        );

        $secondAttempt = $attempt->fresh();
        $secondAnswersCount = ExamAnswer::query()->where('exam_attempt_id', $attempt->id)->count();

        $this->assertEquals($firstScore, $secondAttempt->score);
        $this->assertEquals($firstAnswersCount, $secondAnswersCount);
        $this->assertEquals('user_submit', $secondAttempt->metadata['termination_reason']);
    }

    public function test_submit_attempt_uses_saved_answers_after_timeout(): void
    {
        $class = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $user = User::factory()->create(['school_class_id' => $class->id]);
        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'duration' => 30,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $question = Question::factory()->create(['school_class_id' => $class->id]);
        $correctOption = Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $wrongOption = Option::factory()->create(['question_id' => $question->id, 'is_correct' => false]);
        $exam->questions()->attach($question->id);

        $attempt = $this->service->startExam($user, $exam);
        $attempt->update([
            'started_at' => now()->subMinutes(45),
            'metadata' => [
                ...($attempt->metadata ?? []),
                'saved_answers' => [$question->id => $correctOption->id],
            ],
        ]);

        $this->service->submitAttempt(
            $attempt->fresh(),
            [$question->id => $wrongOption->id],
            ['termination_reason' => 'user_submit']
        );

        $attempt->refresh();

        $this->assertSame(1.0, (float) $attempt->score);
        $this->assertSame('timeout', $attempt->metadata['termination_reason']);
    }
}
