<?php

namespace Database\Factories;

use App\Models\ExamTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamTitle>
 */
class ExamTitleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'is_active' => true,
        ];
    }
}
