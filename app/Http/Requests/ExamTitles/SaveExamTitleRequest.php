<?php

namespace App\Http\Requests\ExamTitles;

use App\Models\ExamTitle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveExamTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $examTitle = $this->route('exam_title');
        $examTitleId = $examTitle instanceof ExamTitle ? $examTitle->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('exam_titles', 'name')->ignore($examTitleId)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            ...$this->validated(),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
