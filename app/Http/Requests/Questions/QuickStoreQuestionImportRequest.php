<?php

namespace App\Http\Requests\Questions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuickStoreQuestionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1', 'max:100'],
            'rows.*.subject_name' => ['required', 'string'],
            'rows.*.topic_name' => ['required', 'string'],
            'rows.*.type' => ['required', 'in:multiple_choice,short_answer,theory'],
            'rows.*.content' => ['required', 'string'],
            'rows.*.image_url' => ['nullable', 'url'],
            'rows.*.explanation' => ['nullable', 'string'],
            'rows.*.level' => ['required', 'in:lp,hp,js,ss'],
            'rows.*.options' => ['nullable', 'array', 'size:4'],
            'rows.*.options.*' => ['required_with:rows.*.options', 'string'],
            'rows.*.correct_answer' => ['nullable', 'in:A,B,C,D,a,b,c,d'],
            'rows.*.marking_scheme' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('rows', []) as $index => $row) {
                $type = $row['type'] ?? null;
                $options = is_array($row['options'] ?? null) ? $row['options'] : [];
                $markingScheme = is_array($row['marking_scheme'] ?? null) ? $row['marking_scheme'] : [];
                $correctAnswer = strtolower((string) ($row['correct_answer'] ?? ''));

                if ($type === 'multiple_choice') {
                    if (count($options) !== 4) {
                        $validator->errors()->add("rows.$index.options", 'Multiple choice imports must include exactly 4 options.');
                    }

                    if (! in_array($correctAnswer, ['a', 'b', 'c', 'd'], true)) {
                        $validator->errors()->add("rows.$index.correct_answer", 'Multiple choice imports must include a valid correct answer.');
                    }
                }

                if (in_array($type, ['short_answer', 'theory'], true) && $markingScheme === []) {
                    $validator->errors()->add("rows.$index.marking_scheme", 'Written imports must include at least one marking point.');
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        /** @var array{rows: array<int, array<string, mixed>>} $validated */
        $validated = $this->validated();

        return array_map(fn (array $row) => [
            'subject_name' => $row['subject_name'],
            'topic_name' => $row['topic_name'],
            'type' => $row['type'],
            'content' => $row['content'],
            'image_url' => $row['image_url'] ?? null,
            'explanation' => $row['explanation'] ?? null,
            'level' => $row['level'],
            'options' => $row['options'] ?? [],
            'correct_answer' => $row['correct_answer'] ?? '',
            'marking_scheme' => $row['marking_scheme'] ?? [],
        ], $validated['rows']);
    }
}
