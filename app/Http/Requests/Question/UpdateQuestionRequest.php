<?php

namespace App\Http\Requests\Question;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create exams');
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'exists:topics,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'content' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'type' => ['required', new Enum(QuestionType::class)],
            'difficulty' => ['required', new Enum(QuestionDifficulty::class)],
            'is_active' => ['boolean'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => ['nullable', 'string'], // Existing options might have IDs
            'options.*.content' => ['required', 'string'],
            'options.*.is_correct' => ['boolean'],
        ];
    }

    /**
     * Ensure at least one option is correct.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasCorrect = collect($this->options)->contains('is_correct', true);
            if (! $hasCorrect) {
                $validator->errors()->add('options', 'At least one option must be marked as correct.');
            }
        });
    }
}