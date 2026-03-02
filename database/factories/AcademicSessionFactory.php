<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicSession>
 */
class AcademicSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->year();

        return [
            'name' => "{$year}/".($year + 1),
            'term' => \App\Enums\Term::FIRST,
            'is_current' => false,
            'start_date' => now(),
            'end_date' => now()->addMonths(4),
        ];
    }
}
