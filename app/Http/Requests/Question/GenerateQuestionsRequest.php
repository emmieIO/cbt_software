<?php

namespace App\Http\Requests\Question;

use App\Enums\QuestionDifficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateQuestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'topic_id' => ['required', 'exists:topics,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
            'difficulty' => ['required', Rule::enum(QuestionDifficulty::class)],
        ];
    }
}
