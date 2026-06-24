<?php

namespace App\Http\Requests\Exams;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamTitle;
use App\Support\AcademicLevels;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'academic_session_id' => ['required', 'string', 'exists:academic_sessions,id'],
            'instructions' => ['nullable', 'string'],
            'duration' => ['nullable', 'string', 'max:255'],
            'class_level' => ['nullable', Rule::in(AcademicLevels::classValues())],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['distinct', 'exists:questions,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $title = (string) $this->input('title');
            $exam = $this->route('exam');

            if (! ($exam instanceof Exam && $exam->title === $title)) {
                $exists = ExamTitle::query()
                    ->where('name', $title)
                    ->where('is_active', true)
                    ->exists();

                if (! $exists) {
                    $validator->errors()->add('title', 'Select a valid exam title.');
                }
            }

            $academicSessionId = (string) $this->input('academic_session_id');

            if (! ($exam instanceof Exam && $exam->academic_session_id === $academicSessionId)) {
                $sessionIsActive = AcademicSession::query()
                    ->whereKey($academicSessionId)
                    ->where('is_active', true)
                    ->exists();

                if (! $sessionIsActive) {
                    $validator->errors()->add('academic_session_id', 'Select an active academic session.');
                }
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
