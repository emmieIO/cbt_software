<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('exam:create');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'school_id' => ['required', 'exists:schools,id'],
            'subject_id' => [
                'required_without:compositions',
                'nullable',
                'exists:subjects,id',
            ],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string'],
            'start_time' => ['nullable', 'date', 'after_or_equal:now'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'compositions' => ['nullable', 'array'],
            'compositions.*.subject_id' => ['required', 'exists:subjects,id'],
            'compositions.*.topic_id' => ['nullable', 'exists:topics,id'],
            'compositions.*.question_count' => ['required', 'integer', 'min:1'],
            'compositions.*.marks_per_question' => ['required', 'numeric', 'min:0.1'],
        ];
    }
}
