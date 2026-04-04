<?php

namespace App\Http\Requests\Question;

use App\Enums\QuestionDifficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GetQuestionsRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
            'difficulty' => ['nullable', new Enum(QuestionDifficulty::class)],
            'level' => ['nullable', 'string', 'in:nursery,primary,secondary'],
        ];
    }
}
