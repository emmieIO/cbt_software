<?php

namespace App\Http\Requests\Exports;

use App\Models\ExamTitle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
            'subject_id' => ['required', 'exists:subjects,id'],
            'level' => ['required', 'in:lp,hp,js,ss'],
            'instructions' => ['nullable', 'string'],
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

            $titleExists = ExamTitle::query()
                ->where('name', (string) $this->input('title'))
                ->where('is_active', true)
                ->exists();

            if (! $titleExists) {
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
