<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'content' => $this->faker->paragraph().'?',
            'explanation' => $this->faker->paragraph(),
            'type' => QuestionType::MULTIPLE_CHOICE,
            'created_by' => User::factory(),
        ];
    }
}
