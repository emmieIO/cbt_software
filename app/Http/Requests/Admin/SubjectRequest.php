<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subject = $this->route('subject');
        $subjectId = $subject instanceof Subject ? $subject->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects')
                    ->where(fn ($q) => $q->where('level', $this->input('level')))
                    ->ignore($subjectId),
            ],
            'description' => ['nullable', 'string'],
            'level' => ['required', 'string', 'in:nursery,primary,secondary'],
        ];
    }
}
