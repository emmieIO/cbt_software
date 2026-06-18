<?php

namespace App\Http\Requests\Topics;

use App\Models\Subject;
use App\Support\AcademicLevels;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveTopicRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'class_level' => ['required', Rule::in(AcademicLevels::classValues())],
            'description' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $subject = Subject::query()->find($this->input('subject_id'));

            if ($subject && ! AcademicLevels::classBelongsToLevel((string) $this->input('class_level'), $subject->level)) {
                $validator->errors()->add('class_level', 'Select a class level that belongs to the selected school level.');
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
