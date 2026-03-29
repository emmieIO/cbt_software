<?php

namespace App\Http\Requests\Question;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bank:create');
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'topic_id' => ['required', 'exists:topics,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'content' => ['required', 'string', 'min:10'],
            'explanation' => ['nullable', 'string'],
            'type' => ['required', new Enum(QuestionType::class)],
            'difficulty' => ['required', new Enum(QuestionDifficulty::class)],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'is_active' => ['boolean'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*.content' => ['required', 'string'],
            'options.*.is_correct' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject_id.required' => 'Please select a subject area.',
            'topic_id.required' => 'A curriculum topic must be selected.',
            'school_class_id.required' => 'Target academic level is required.',
            'content.required' => 'The question body cannot be empty.',
            'content.min' => 'The question content seems too short. Please provide more detail.',
            'options.required' => 'You must provide response choices.',
            'options.min' => 'Each question needs at least 2 options.',
            'options.*.content.required' => 'Option content is required for all choices.',
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
