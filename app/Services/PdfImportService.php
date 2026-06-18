<?php

namespace App\Services;

use App\Ai\Agents\QuestionParserAgent;
use App\Support\AcademicLevels;
use App\Support\MathContent;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Spatie\PdfToText\Pdf;

class PdfImportService
{
    /**
     * Build a preview payload from an uploaded PDF file.
     *
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     total: int,
     *     valid: int,
     *     errors: int,
     *     new_subjects: array<int, string>,
     *     new_topics: array<int, string>
     * }
     */
    public function buildPreview(UploadedFile $file): array
    {
        $text = Pdf::getText($file->getRealPath(), options: ['layout']);

        if (trim($text) === '') {
            throw new RuntimeException('No readable text was found in this PDF. It may be scanned or image-only.');
        }

        $response = QuestionParserAgent::make()->prompt(
            "Extract questions from this PDF text:\n\n".$text,
            timeout: 120,
        );

        $data = $response['questions'] ?? [];

        $rows = [];
        $subjectKeys = [];
        $topicNames = [];

        foreach ($data as $index => $item) {
            $previewRow = $this->validateAndBuildRow([
                'index' => $index + 1,
                'subject_name' => trim($item['subject_name'] ?? ''),
                'topic_name' => trim($item['topic_name'] ?? ''),
                'type' => $item['type'] ?? 'multiple_choice',
                'content' => MathContent::normalize($item['content'] ?? ''),
                'image_url' => $item['image_url'] ?? null,
                'explanation' => MathContent::normalize($item['explanation'] ?? '') ?: null,
                'level' => $item['level'] ?? null,
                'class_level' => $item['class_level'] ?? null,
                'options' => array_map(fn (mixed $option): string => MathContent::normalize($option), $item['options'] ?? []),
                'correct_answer' => MathContent::normalize($item['correct_answer'] ?? '') ?: null,
                'marking_scheme' => array_map(
                    fn (array $point): array => [
                        ...$point,
                        'point' => MathContent::normalize($point['point'] ?? ''),
                    ],
                    $item['marking_scheme'] ?? [],
                ),
            ]);

            $rows[] = $previewRow;

            if (! empty($previewRow['subject_name'])) {
                $level = $previewRow['level'] ?? 'js';
                $subjectKeys[$previewRow['subject_name'].'::'.$level] = [
                    'name' => $previewRow['subject_name'],
                    'level' => $level,
                ];
            }
            if (! empty($previewRow['topic_name'])) {
                $topicNames[$previewRow['topic_name'].'::'.($previewRow['class_level'] ?? '')] = [
                    'name' => $previewRow['topic_name'],
                    'class_level' => $previewRow['class_level'] ?? null,
                ];
            }
        }

        return (new QuestionImportPreviewService)->formatPreviewPayload($rows, $subjectKeys, $topicNames);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateAndBuildRow(array $data): array
    {
        $errors = [];

        if (empty($data['subject_name'])) {
            $errors[] = 'Subject is required.';
        }
        if (empty($data['topic_name'])) {
            $errors[] = 'Topic is required.';
        }
        if (empty($data['content'])) {
            $errors[] = 'Question content is required.';
        }
        if ($data['image_url'] && ! filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Image URL must be a valid URL.';
        }
        if ($data['level'] && ! in_array($data['level'], ['lp', 'hp', 'js', 'ss'])) {
            $errors[] = 'Invalid level.';
        }
        if ($data['class_level'] && ! in_array($data['class_level'], AcademicLevels::classValues(), true)) {
            $errors[] = 'Invalid class level.';
        }
        if ($data['level'] && $data['class_level'] && ! AcademicLevels::classBelongsToLevel($data['class_level'], $data['level'])) {
            $errors[] = 'Class level does not belong to selected level.';
        }

        if ($data['type'] === 'multiple_choice') {
            $letters = ['a', 'b', 'c', 'd'];
            foreach ($letters as $index => $letter) {
                if (empty($data['options'][$index])) {
                    $errors[] = 'Option '.strtoupper($letter).' is required.';
                }
            }

            if (empty($data['correct_answer']) || ! in_array(strtolower($data['correct_answer']), $letters)) {
                $errors[] = 'Correct answer must be A, B, C, or D.';
            }
        }

        if (in_array($data['type'], ['short_answer', 'theory'], true) && empty($data['marking_scheme'])) {
            $errors[] = 'Written questions require at least one marking point.';
        }

        $data['valid'] = $errors === [];
        $data['errors'] = $errors;

        return $data;
    }
}
