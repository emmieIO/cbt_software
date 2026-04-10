<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('student') instanceof User ? $this->route('student')->id : null)],
            'username' => ['prohibited'],
            'school_id' => ['required', 'exists:schools,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ];
    }
}
