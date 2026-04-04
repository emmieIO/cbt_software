<?php

namespace App\Services\Question;

use App\DTOs\OptionDTO;
use App\DTOs\QuestionDTO;

class QuestionDtoFactory
{
    /**
     * Build DTOs for spreadsheet batch question creation.
     *
     * @param  array<int, array<string, mixed>>  $questions
     * @param  array<int, string|null>  $imagePathsByIndex
     * @return array<int, QuestionDTO>
     */
    public function makeBatch(array $questions, array $imagePathsByIndex = []): array
    {
        $dtos = [];

        foreach ($questions as $index => $qData) {
            $options = array_map(
                fn (array $option) => new OptionDTO($option['content'], $option['is_correct']),
                $qData['options']
            );

            $dtos[] = new QuestionDTO(
                topic_id: $qData['topic_id'],
                school_class_id: $qData['school_class_id'],
                content: $qData['content'],
                explanation: $qData['explanation'] ?? null,
                type: $qData['type'],
                difficulty: $qData['difficulty'],
                options: $options,
                image_path: $imagePathsByIndex[$index] ?? null
            );
        }

        return $dtos;
    }
}
