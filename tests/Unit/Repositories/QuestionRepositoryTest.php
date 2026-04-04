<?php

namespace Tests\Unit\Repositories;

use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Topic;
use App\Models\User;
use App\Repositories\Eloquent\EloquentQuestionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected EloquentQuestionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentQuestionRepository;
    }

    public function test_can_create_question_with_options(): void
    {
        $topic = Topic::factory()->create();
        $class = SchoolClass::factory()->create();
        $user = User::factory()->create();

        $data = [
            'topic_id' => $topic->id,
            'school_class_id' => $class->id,
            'created_by' => $user->id,
            'content' => 'What is 2+2?',
            'type' => 'multiple_choice',
            'difficulty' => 'easy',
        ];

        $options = [
            ['content' => '4', 'is_correct' => true],
            ['content' => '5', 'is_correct' => false],
        ];

        $question = $this->repository->create($data, $options);

        $this->assertDatabaseHas('questions', ['content' => 'What is 2+2?']);
        $this->assertCount(2, $question->options);
        $this->assertTrue($question->options->where('is_correct', true)->isNotEmpty());
    }

    public function test_can_get_questions_for_exam_composition(): void
    {
        $topic = Topic::factory()->create();

        // Create 10 easy questions
        Question::factory()->count(10)->create([
            'topic_id' => $topic->id,
            'difficulty' => 'easy',
        ]);

        // Create 5 hard questions
        Question::factory()->count(5)->create([
            'topic_id' => $topic->id,
            'difficulty' => 'hard',
        ]);

        $results = $this->repository->getForComposition($topic->id, 'easy', 5);

        $this->assertCount(5, $results);
        foreach ($results as $q) {
            $this->assertEquals('easy', $q->difficulty->value);
        }
    }
}
