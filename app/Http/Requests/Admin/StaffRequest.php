<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('staff') instanceof User ? $this->route('staff')->id : null)],
            'username' => ['prohibited'],
            'school_ids' => ['required', 'array', 'min:1'],
            'school_ids.*' => ['exists:schools,id'],
            'primary_school_id' => ['nullable', 'exists:schools,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ];
    }
}
