<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\Topic;
use Exception;
use Illuminate\Http\UploadedFile;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class QuestionImportPreviewService
{
    /**
     * Build a preview payload from an uploaded spreadsheet.
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
        $reader = $this->makeReader($file->getClientOriginalExtension());
        $reader->open($file->getRealPath());

        $rows = [];
        $subjectKeys = [];
        $topicNames = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $isFirst = true;

            foreach ($sheet->getRowIterator() as $row) {
                if ($isFirst) {
                    $isFirst = false;

                    continue;
                }

                $cells = $row->toArray();
                if (empty(trim($cells[3] ?? ''))) {
                    continue;
                }

                $previewRow = $this->buildPreviewRow($cells, count($rows) + 2);
                $rows[] = $previewRow;

                if (! empty($previewRow['subject_name'])) {
                    $level = $previewRow['level'] ?? 'js';
                    $subjectKeys[$previewRow['subject_name'].'::'.$level] = [
                        'name' => $previewRow['subject_name'],
                        'level' => $level,
                    ];
                }
                if (! empty($previewRow['topic_name'])) {
                    $topicNames[$previewRow['topic_name']] = true;
                }
            }
        }

        $reader->close();

        $existingSubjects = $subjectKeys === []
            ? []
            : Subject::query()
                ->where(function ($query) use ($subjectKeys) {
                    foreach ($subjectKeys as $subject) {
                        $query->orWhere(fn ($subjectQuery) => $subjectQuery
                            ->where('name', $subject['name'])
                            ->where('level', $subject['level']));
                    }
                })
                ->get(['name', 'level'])
                ->map(fn (Subject $subject) => $subject->name.'::'.$subject->level)
                ->all();
        $existingTopics = Topic::query()
            ->whereIn('name', array_keys($topicNames))
            ->pluck('name')
            ->toArray();

        return [
            'rows' => $rows,
            'total' => count($rows),
            'valid' => count(array_filter($rows, fn ($row) => $row['valid'])),
            'errors' => count(array_filter($rows, fn ($row) => ! $row['valid'])),
            'new_subjects' => array_values(array_map(
                fn (array $subject) => $subject['name'].' ('.strtoupper($subject['level']).')',
                array_intersect_key($subjectKeys, array_flip(array_diff(array_keys($subjectKeys), $existingSubjects))),
            )),
            'new_topics' => array_values(array_diff(array_keys($topicNames), $existingTopics)),
        ];
    }

    /**
     * @param array<int, mixed> $cells
     * @return array<string, mixed>
     */
    private function buildPreviewRow(array $cells, int $rowNumber): array
    {
        $subjectName = trim($cells[0] ?? '');
        $topicName = trim($cells[1] ?? '');
        $type = trim($cells[2] ?? '') === 'theory' ? 'theory' : 'multiple_choice';
        $content = trim($cells[3] ?? '');
        $imageUrl = trim($cells[4] ?? '');
        $explanation = trim($cells[10] ?? '');
        $level = strtolower(trim($cells[13] ?? ''));
        $errors = [];

        if ($subjectName === '') {
            $errors[] = 'Subject is required.';
        }
        if ($topicName === '') {
            $errors[] = 'Topic is required.';
        }
        if ($content === '') {
            $errors[] = 'Question content is required.';
        }
        if ($imageUrl !== '' && ! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Image URL must be a valid URL.';
        }
        if ($level !== '' && ! in_array($level, ['lp', 'hp', 'js', 'ss'])) {
            $errors[] = 'Invalid level.';
        }

        $options = [];
        $correctAnswer = null;

        if ($type === 'multiple_choice') {
            $letters = ['a', 'b', 'c', 'd'];

            for ($i = 0; $i < 4; $i++) {
                $option = trim($cells[5 + $i] ?? '');
                if ($option === '') {
                    $errors[] = 'Option '.strtoupper($letters[$i]).' is required.';
                }
                $options[] = $option;
            }

            $correctAnswer = strtoupper(trim($cells[9] ?? ''));
            if (! in_array(strtolower($correctAnswer), $letters)) {
                $errors[] = 'Correct answer must be A, B, C, or D.';
            }
        }

        return [
            'index' => $rowNumber,
            'valid' => $errors === [],
            'errors' => $errors,
            'subject_name' => $subjectName,
            'topic_name' => $topicName,
            'type' => $type,
            'content' => $content,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'explanation' => $explanation !== '' ? $explanation : null,
            'level' => $level !== '' ? $level : null,
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'marking_scheme' => $this->buildMarkingScheme($cells),
        ];
    }

    /**
     * @param array<int, mixed> $cells
     * @return array<int, array{point: string, weight: int}>
     */
    private function buildMarkingScheme(array $cells): array
    {
        $points = ! empty($cells[11]) ? explode('|', (string) $cells[11]) : [];
        $weights = ! empty($cells[12]) ? explode('|', (string) $cells[12]) : [];
        $scheme = [];

        foreach ($points as $index => $point) {
            $trimmedPoint = trim($point);
            if ($trimmedPoint === '') {
                continue;
            }

            $scheme[] = [
                'point' => $trimmedPoint,
                'weight' => (int) ($weights[$index] ?? 1),
            ];
        }

        return $scheme;
    }

    private function makeReader(string $extension): ReaderInterface
    {
        return match (strtolower($extension)) {
            'csv' => new CsvReader,
            'xlsx' => new XlsxReader,
            default => throw new Exception('Unsupported file type. Please upload an XLSX or CSV file.'),
        };
    }
}
