<?php

namespace Database\Factories;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeDocumentChunk>
 */
class KnowledgeDocumentChunkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $content = fake()->paragraphs(3, true);

        return [
            'chunk_version' => 1,
            'knowledge_document_id' => KnowledgeDocument::factory(),
            'sequence' => fake()->unique()->numberBetween(1, 10_000),
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'estimated_token_count' => str_word_count($content),
            'metadata' => null,
            'embedding' => null,
            'embedding_provider' => null,
            'embedding_model' => null,
            'embedding_dimensions' => null,
            'embedded_content_hash' => null,
            'embedded_at' => null,
        ];
    }
}
