<?php

namespace Database\Factories;

use App\Models\QuestionGenerationRun;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionGenerationRun>
 */
class QuestionGenerationRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requested_by' => User::factory(),
            'subject_id' => Subject::factory()->state(['level' => 'js']),
            'topic_id' => Topic::factory()->state(['class_level' => '7']),
            'level' => 'js',
            'class_level' => '7',
            'requested_count' => 3,
            'type_distribution' => [
                'multiple_choice' => 1,
                'short_answer' => 1,
                'theory' => 1,
            ],
            'difficulty' => 'medium',
            'additional_instructions' => null,
            'status' => QuestionGenerationRun::STATUS_PENDING,
        ];
    }
}
