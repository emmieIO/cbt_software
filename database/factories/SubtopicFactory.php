<?php

namespace Database\Factories;

use App\Models\Subtopic;
use App\Models\Topic;
use App\Services\CurriculumMappingService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subtopic>
 */
class SubtopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return ['topic_id' => Topic::factory(), 'name' => $name, 'normalized_name' => app(CurriculumMappingService::class)->normalizedName($name)];
    }
}
