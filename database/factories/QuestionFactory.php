<?php

namespace Database\Factories;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\SchoolClass;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'school_class_id' => SchoolClass::factory(),
            'content' => $this->faker->paragraph().'?',
            'explanation' => $this->faker->paragraph(),
            'type' => QuestionType::MULTIPLE_CHOICE,
            'difficulty' => $this->faker->randomElement(QuestionDifficulty::cases()),
            'created_by' => User::factory(),
            'is_active' => true,
        ];
    }
}
