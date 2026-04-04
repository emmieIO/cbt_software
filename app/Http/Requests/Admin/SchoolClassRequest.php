<?php

namespace App\Http\Requests\Admin;

use App\Enums\ClassLevel;
use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolClass = $this->route('schoolClass');
        $schoolClassId = $schoolClass instanceof SchoolClass ? $schoolClass->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('school_classes')
                    ->where(fn ($q) => $q->where('level', $this->input('level')))
                    ->ignore($schoolClassId),
            ],
            'level' => ['required', Rule::enum(ClassLevel::class)],
        ];
    }
}
