<?php

namespace Database\Factories;

use App\Models\GeneratedQuestionDraft;
use App\Models\QuestionGenerationRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratedQuestionDraft>
 */
class GeneratedQuestionDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_generation_run_id' => QuestionGenerationRun::factory(),
            'sequence' => fake()->unique()->numberBetween(1, 10_000),
            'type' => 'multiple_choice',
            'difficulty' => 'medium',
            'content' => fake()->sentence().'?',
            'explanation' => fake()->sentence(),
            'expected_answer' => null,
            'options' => [
                ['content' => fake()->word(), 'is_correct' => true],
                ['content' => fake()->word(), 'is_correct' => false],
                ['content' => fake()->word(), 'is_correct' => false],
                ['content' => fake()->word(), 'is_correct' => false],
            ],
            'marking_scheme' => null,
            'source_references' => [],
            'validation_errors' => null,
            'warnings' => null,
            'is_valid' => true,
        ];
    }
}
