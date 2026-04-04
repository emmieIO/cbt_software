<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_ids' => ['required', 'array'],
            'question_ids.*' => ['exists:questions,id'],
        ];
    }
}
