<?php

namespace Database\Factories;

use App\Enums\ClassLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => strtoupper($name),
            'slug' => Str::slug($name),
            'level' => $this->faker->randomElement(ClassLevel::cases()),
        ];
    }
}
