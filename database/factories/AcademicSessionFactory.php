<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicSession>
 */
class AcademicSessionFactory extends Factory
{
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2000, 2090);

        return [
            'name' => "{$startYear}/".($startYear + 1),
            'starts_at' => "{$startYear}-09-01",
            'ends_at' => ($startYear + 1).'-08-31',
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }
}
