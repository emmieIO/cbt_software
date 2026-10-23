<?php

namespace Database\Factories;

use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeDocument>
 */
class KnowledgeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $path = 'knowledge-documents/'.Str::uuid().'.pdf';

        return [
            'title' => fake()->sentence(4),
            'document_type' => fake()->randomElement(KnowledgeDocument::DOCUMENT_TYPES),
            'description' => fake()->optional()->sentence(),
            'original_name' => fake()->slug().'.pdf',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => fake()->numberBetween(10_000, 2_000_000),
            'checksum' => hash('sha256', $path),
            'processing_status' => KnowledgeDocument::PROCESSING_PENDING,
            'approval_status' => KnowledgeDocument::APPROVAL_PENDING,
            'embedding_status' => KnowledgeDocument::EMBEDDING_PENDING,
            'metadata' => null,
            'uploaded_by' => User::factory(),
        ];
    }
}
