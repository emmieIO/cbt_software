<?php

namespace App\Http\Requests\Question;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BatchStoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bank:create');
    }

    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.topic_id' => ['required', 'exists:topics,id'],
            'questions.*.school_class_id' => ['required', 'exists:school_classes,id'],
            'questions.*.prospective_class_id' => ['nullable', 'exists:prospective_classes,id'],
            'questions.*.content' => ['required', 'string'],
            'questions.*.explanation' => ['nullable', 'string'],
            'questions.*.type' => ['required', new Enum(QuestionType::class)],
            'questions.*.difficulty' => ['required', new Enum(QuestionDifficulty::class)],
            'questions.*.image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*.content' => ['required', 'string'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'questions.*.topic_id.required' => 'The topic is required for all rows.',
            'questions.*.content.required' => 'The question text is required for all rows.',
            'questions.*.options.min' => 'Each question must have at least 2 options.',
        ];
    }

    /**
     * Custom validation logic for correctness.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->questions ?? [] as $index => $question) {
                $hasCorrect = collect($question['options'] ?? [])->contains('is_correct', true);
                if (! $hasCorrect) {
                    $validator->errors()->add("questions.$index.options", 'At least one option must be marked as correct.');
                }
            }
        });
    }
}
