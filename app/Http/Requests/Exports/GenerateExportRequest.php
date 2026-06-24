<?php

namespace App\Http\Requests\Exports;

use App\Models\AcademicSession;
use App\Models\ExamTitle;
use App\Support\AcademicLevels;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateExportRequest extends FormRequest
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
            'subject_id' => ['required', 'exists:subjects,id'],
            'level' => ['required', 'in:lp,hp,js,ss'],
            'class_level' => ['required', Rule::in(AcademicLevels::classValues())],
            'instructions' => ['nullable', 'string'],
            'duration' => ['nullable', 'string', 'max:255'],
            'mcq_count' => ['required', 'integer', 'min:0', 'max:100'],
            'theory_count' => ['required', 'integer', 'min:0', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mcqCount = (int) $this->input('mcq_count', 0);
            $theoryCount = (int) $this->input('theory_count', 0);

            if (($mcqCount + $theoryCount) < 1) {
                $validator->errors()->add('mcq_count', 'Select at least one question to generate an export.');
            }

            if (! AcademicLevels::classBelongsToLevel((string) $this->input('class_level'), (string) $this->input('level'))) {
                $validator->errors()->add('class_level', 'Select a class level that belongs to the selected school level.');
            }

            $titleExists = ExamTitle::query()
                ->where('name', (string) $this->input('title'))
                ->where('is_active', true)
                ->exists();

            if (! $titleExists) {
                $validator->errors()->add('title', 'Select a valid exam title.');
            }

            $sessionIsActive = AcademicSession::query()
                ->whereKey((string) $this->input('academic_session_id'))
                ->where('is_active', true)
                ->exists();

            if (! $sessionIsActive) {
                $validator->errors()->add('academic_session_id', 'Select an active academic session.');
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
