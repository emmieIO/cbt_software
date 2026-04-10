<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $candidate = $this->route('candidate');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$candidate->id],
            'username' => ['prohibited'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'prospective_class_id' => ['required', 'exists:prospective_classes,id'],
        ];
    }
}
