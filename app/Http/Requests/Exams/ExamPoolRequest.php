<?php

namespace App\Http\Requests\Exams;

use App\Support\AcademicLevels;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExamPoolRequest extends FormRequest
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
            'subject_id' => ['required', 'exists:subjects,id'],
            'level' => ['required', 'in:lp,hp,js,ss'],
            'class_level' => ['required', Rule::in(AcademicLevels::classValues())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! AcademicLevels::classBelongsToLevel((string) $this->input('class_level'), (string) $this->input('level'))) {
                $validator->errors()->add('class_level', 'Select a class level that belongs to the selected school level.');
            }
        });
    }
}
