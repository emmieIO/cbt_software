<?php

namespace App\Http\Requests\Questions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkStoreQuestionRequest extends FormRequest
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
            'questions' => ['required', 'array', 'min:1', 'max:100'],
            'questions.*.type' => ['required', 'in:multiple_choice,theory'],
            'questions.*.topic_id' => ['required', 'exists:topics,id'],
            'questions.*.content' => ['required', 'string'],
            'questions.*.level' => ['required', 'in:lp,hp,js,ss'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.content' => ['required_with:questions.*.options', 'string'],
            'questions.*.options.*.is_correct' => ['nullable', 'boolean'],
            'questions.*.marking_scheme' => ['nullable', 'array'],
            'questions.*.marking_scheme.*.point' => ['required_with:questions.*.marking_scheme', 'string'],
            'questions.*.marking_scheme.*.weight' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('questions', []) as $index => $question) {
                $type = $question['type'] ?? null;
                $options = is_array($question['options'] ?? null) ? $question['options'] : [];
                $markingScheme = is_array($question['marking_scheme'] ?? null) ? $question['marking_scheme'] : [];

                if ($type === 'multiple_choice') {
                    if (count($options) !== 4) {
                        $validator->errors()->add("questions.$index.options", 'Multiple choice questions must have exactly 4 options.');
                    }

                    $correctCount = count(array_filter($options, fn ($option) => ($option['is_correct'] ?? false) === true));
                    if ($correctCount !== 1) {
                        $validator->errors()->add("questions.$index.options", 'Multiple choice questions must have exactly one correct answer.');
                    }
                }

                if ($type === 'theory') {
                    if ($markingScheme === []) {
                        $validator->errors()->add("questions.$index.marking_scheme", 'Theory questions must include at least one marking point.');
                    }
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function questions(): array
    {
        /** @var array{questions: array<int, array<string, mixed>>} $validated */
        $validated = $this->validated();

        return $validated['questions'];
    }
}
