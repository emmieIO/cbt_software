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
        $staff = $this->route('staff');
        $staffId = $staff instanceof User ? $staff->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staffId)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($staffId)],
            'school_ids' => ['required', 'array', 'min:1'],
            'school_ids.*' => ['exists:schools,id'],
            'primary_school_id' => ['nullable', 'exists:schools,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ];
    }
}
