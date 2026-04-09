<?php

namespace App\Services;

use App\DTOs\OptionDTO;
use App\DTOs\QuestionDTO;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use BackedEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

class BulkImportService
{
    public function __construct(protected QuestionService $questionService) {}

    /**
     * Import questions from a CSV or Excel file.
     *
     * @param  array<string, mixed>  $setup
     *
     * @throws ValidationException
     */
    public function import(UploadedFile $file, string $userId, array $setup = []): int
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $reader = match ($extension) {
            'csv', 'txt' => new CsvReader,
            'xlsx' => new XlsxReader,
            default => throw ValidationException::withMessages(['file' => ['Unsupported file format. Please upload a .csv or .xlsx file.']]),
        };

        $reader->open($file->getRealPath());

        $user = User::query()->with('schools:id')->find($userId);
        if (! $user) {
            throw ValidationException::withMessages(['file' => ['Unable to resolve importing user. Please log in again and retry.']]);
        }

        $isSystemAdmin = $user->can('sys:manage_settings');
        $allowedSchoolIds = $user->schools->pluck('id')->filter()->values();
        if ($allowedSchoolIds->isEmpty() && $user->school_id) {
            $allowedSchoolIds = collect([$user->school_id]);
        }

        $setupLevel = strtolower(trim((string) ($setup['level'] ?? '')));
        $setupDifficultyRaw = trim((string) ($setup['difficulty'] ?? ''));
        $setupDifficulty = QuestionDifficulty::tryFrom($setupDifficultyRaw);
        if ($setupDifficultyRaw === '' || ! $setupDifficulty) {
            throw ValidationException::withMessages(['difficulty' => ['Select a valid setup difficulty before importing.']]);
        }

        $setupTypeRaw = trim((string) ($setup['question_type'] ?? ''));
        $setupType = $setupTypeRaw !== '' ? QuestionType::tryFrom($setupTypeRaw) : null;
        if ($setupTypeRaw !== '' && ! $setupType) {
            throw ValidationException::withMessages(['question_type' => ['Invalid setup question type selected.']]);
        }

        $setupClass = $this->resolveClass(
            classId: $setup['school_class_id'] ?? null,
            className: null,
            isSystemAdmin: $isSystemAdmin,
            allowedSchoolIds: $allowedSchoolIds
        );

        $setupSubject = $this->resolveSubject(
            subjectId: $setup['subject_id'] ?? null,
            subjectName: null,
            classLevel: $setupClass ? $this->normalizeLevelValue($setupClass->level) : null
        );

        if ($setupClass && $setupLevel !== '' && $this->normalizeLevelValue($setupClass->level) !== $setupLevel) {
            throw ValidationException::withMessages([
                'level' => ['Selected level does not match selected class level.'],
            ]);
        }

        if (
            $setupSubject
            && $setupClass
            && $this->normalizeLevelValue($setupSubject->level) !== $this->normalizeLevelValue($setupClass->level)
        ) {
            throw ValidationException::withMessages([
                'subject_id' => ['Selected subject level does not match selected class level.'],
            ]);
        }

        $importedCount = 0;
        $sheetNumber = 0;
        $rowNumber = 0;
        $errorLocation = 'unknown';

        DB::beginTransaction();
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $sheetNumber++;
                $rowNumber = 0;
                $headers = [];

                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    $errorLocation = "sheet {$sheetNumber}, row {$rowNumber}";

                    $data = $row->toArray();
                    if ($rowNumber === 1) {
                        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $data);
                        continue;
                    }

                    $rowData = [];
                    foreach ($headers as $index => $header) {
                        if ($header === '') {
                            continue;
                        }
                        $rowData[$header] = trim((string) ($data[$index] ?? ''));
                    }

                    if (collect($rowData)->filter(fn ($v) => $v !== '')->isEmpty()) {
                        continue;
                    }

                    $class = $setupClass ?? $this->resolveClass(
                        classId: null,
                        className: $rowData['class_name'] ?? null,
                        isSystemAdmin: $isSystemAdmin,
                        allowedSchoolIds: $allowedSchoolIds
                    );
                    if (! $class) {
                        throw new \RuntimeException('Class could not be resolved. Provide setup class or class_name in sheet.');
                    }

                    if ($setupLevel !== '' && $this->normalizeLevelValue($class->level) !== $setupLevel) {
                        throw new \RuntimeException("Class '{$class->name}' does not match selected setup level '{$setupLevel}'.");
                    }

                    $subject = $setupSubject ?? $this->resolveSubject(
                        subjectId: null,
                        subjectName: $rowData['subject_name'] ?? null,
                        classLevel: $this->normalizeLevelValue($class->level)
                    );
                    if (! $subject) {
                        throw new \RuntimeException('Subject could not be resolved. Provide setup subject or subject_name in sheet.');
                    }

                    $topicName = $this->normalizeText($rowData['topic_name'] ?? '');
                    if ($topicName === '') {
                        throw new \RuntimeException('topic_name is required.');
                    }

                    $content = $this->normalizeText($rowData['content'] ?? '');
                    if ($content === '') {
                        throw new \RuntimeException('content is required.');
                    }

                    $typeRaw = $this->normalizeText($rowData['type'] ?? ($rowData['question_type'] ?? ''));
                    $resolvedType = $setupType ?? QuestionType::tryFrom($typeRaw);
                    if (! $resolvedType) {
                        throw new \RuntimeException('Question type is missing or invalid.');
                    }

                    $correctLetter = strtoupper($this->normalizeText($rowData['correct_option_letter'] ?? ''));
                    if (! in_array($correctLetter, ['A', 'B', 'C', 'D'], true)) {
                        throw new \RuntimeException("correct_option_letter must be one of A, B, C, D; '{$correctLetter}' given.");
                    }

                    $optionsData = [
                        ['content' => $this->normalizeText($rowData['option_a'] ?? ''), 'letter' => 'A'],
                        ['content' => $this->normalizeText($rowData['option_b'] ?? ''), 'letter' => 'B'],
                        ['content' => $this->normalizeText($rowData['option_c'] ?? ''), 'letter' => 'C'],
                        ['content' => $this->normalizeText($rowData['option_d'] ?? ''), 'letter' => 'D'],
                    ];

                    $nonEmptyOptions = collect($optionsData)->filter(fn ($opt) => $opt['content'] !== '')->count();
                    if ($nonEmptyOptions < 2) {
                        throw new \RuntimeException('At least two options are required.');
                    }

                    $correctOption = collect($optionsData)->firstWhere('letter', $correctLetter);
                    if (! $correctOption || $correctOption['content'] === '') {
                        throw new \RuntimeException("Correct option '{$correctLetter}' has no content.");
                    }

                    $topic = $this->findOrCreateTopic($subject, $class, $topicName);

                    $options = array_map(
                        fn ($opt) => new OptionDTO(content: $opt['content'], is_correct: $opt['letter'] === $correctLetter),
                        $optionsData
                    );

                    $dto = new QuestionDTO(
                        topic_id: $topic->id,
                        school_class_id: $class->id,
                        content: $content,
                        explanation: ($rowData['explanation'] ?? '') !== '' ? $rowData['explanation'] : null,
                        type: $resolvedType,
                        difficulty: $setupDifficulty,
                        options: $options,
                        image_path: $this->normalizeExternalImagePath($rowData['image_url'] ?? null)
                    );

                    $this->questionService->createQuestion($dto, $userId);
                    $importedCount++;
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'file' => ["Error at {$errorLocation}: ".$e->getMessage()],
            ]);
        } finally {
            $reader->close();
        }

        return $importedCount;
    }

    private function resolveClass(mixed $classId, mixed $className, bool $isSystemAdmin, Collection $allowedSchoolIds): ?SchoolClass
    {
        $query = SchoolClass::query();

        if (! $isSystemAdmin) {
            if ($allowedSchoolIds->isEmpty()) {
                throw new \RuntimeException('You are not attached to any school branch. Contact admin before importing questions.');
            }
            $query->whereIn('school_id', $allowedSchoolIds->all());
        }

        if (filled($classId)) {
            return (clone $query)->where('id', $classId)->first();
        }

        if (filled($className)) {
            return (clone $query)->where('name', trim((string) $className))->first();
        }

        return null;
    }

    private function resolveSubject(mixed $subjectId, mixed $subjectName, ?string $classLevel): ?Subject
    {
        $query = Subject::query();
        if ($classLevel) {
            $query->where('level', $classLevel);
        }

        if (filled($subjectId)) {
            return (clone $query)->where('id', $subjectId)->first();
        }

        if (filled($subjectName)) {
            return (clone $query)->where('name', trim((string) $subjectName))->first();
        }

        return null;
    }

    private function findOrCreateTopic(Subject $subject, SchoolClass $class, string $topicName): Topic
    {
        $normalizedName = $this->normalizeText($topicName);
        $needle = strtolower($normalizedName);

        $topic = Topic::query()
            ->where('subject_id', $subject->id)
            ->where('school_class_id', $class->id)
            ->whereRaw('LOWER(name) = ?', [$needle])
            ->first();

        if ($topic) {
            return $topic;
        }

        $sharedTopic = Topic::query()
            ->where('subject_id', $subject->id)
            ->whereNull('school_class_id')
            ->whereRaw('LOWER(name) = ?', [$needle])
            ->first();

        if ($sharedTopic) {
            return $sharedTopic;
        }

        return Topic::create([
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
            'name' => $normalizedName,
            'slug' => $this->generateUniqueTopicSlug($normalizedName, $class->name),
        ]);
    }

    private function generateUniqueTopicSlug(string $topicName, string $className): string
    {
        $base = Str::slug($topicName.'-'.$className);
        $base = $base !== '' ? $base : Str::slug($topicName);
        $base = $base !== '' ? $base : 'topic';

        $slug = $base;
        $suffix = 2;
        while (Topic::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function normalizeExternalImagePath(mixed $imageUrl): ?string
    {
        $raw = trim((string) ($imageUrl ?? ''));
        if ($raw === '') {
            return null;
        }

        if (! filter_var($raw, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('image_url must be a valid public URL.');
        }

        if (! str_starts_with($raw, 'http://') && ! str_starts_with($raw, 'https://')) {
            throw new \RuntimeException('image_url must start with http:// or https://');
        }

        if (preg_match('#drive\.google\.com/file/d/([^/]+)#i', $raw, $matches) === 1) {
            return 'https://drive.google.com/uc?export=view&id='.$matches[1];
        }
        if (preg_match('#drive\.google\.com/open\?id=([^&]+)#i', $raw, $matches) === 1) {
            return 'https://drive.google.com/uc?export=view&id='.$matches[1];
        }

        return $raw;
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return $normalized;
    }

    private function normalizeText(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value)) ?? '';
    }

    private function normalizeLevelValue(mixed $level): ?string
    {
        if ($level instanceof BackedEnum) {
            return (string) $level->value;
        }

        if (is_string($level) && $level !== '') {
            return $level;
        }

        return null;
    }
}
