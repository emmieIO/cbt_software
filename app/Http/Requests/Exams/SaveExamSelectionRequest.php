<?php

namespace App\Http\Requests\Exams;

use App\Models\Exam;
use App\Models\ExamTitle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveExamSelectionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['distinct', 'exists:questions,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $title = (string) $this->input('title');
            $exam = $this->route('exam');

            if ($exam instanceof Exam && $exam->title === $title) {
                return;
            }

            $exists = ExamTitle::query()
                ->where('name', $title)
                ->where('is_active', true)
                ->exists();

            if (! $exists) {
                $validator->errors()->add('title', 'Select a valid exam title.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->validated();
    }
}
