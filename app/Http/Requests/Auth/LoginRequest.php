<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login_id' => ['required', 'string'], // This can be email, student_id, etc.
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Get the credentials based on the login portal.
     */
    public function credentials(): array
    {
        $loginId = $this->input('login_id');
        $field = filter_var($loginId, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $field => $loginId,
            'password' => $this->password,
        ];
    }
}
