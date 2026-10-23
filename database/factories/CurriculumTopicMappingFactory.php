<?php

namespace Database\Factories;

use App\Models\CurriculumTopicMapping;
use App\Models\KnowledgeDocument;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumTopicMapping>
 */
class CurriculumTopicMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'knowledge_document_id' => KnowledgeDocument::factory(),
            'topic_id' => Topic::factory(),
            'subject_id' => fn (array $attributes) => Topic::query()->findOrFail($attributes['topic_id'])->subject_id,
            'class_level' => fn (array $attributes) => Topic::query()->findOrFail($attributes['topic_id'])->class_level,
            'detected_topic' => fake()->words(3, true),
            'identity_key' => hash('sha256', fake()->uuid()),
            'chunk_version' => 1,
            'status' => 'suggested',
            'evidence_status' => 'current',
            'provenance' => 'ai',
        ];
    }
}
