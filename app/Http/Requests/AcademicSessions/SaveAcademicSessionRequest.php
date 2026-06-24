<?php

namespace App\Http\Requests\AcademicSessions;

use App\Models\AcademicSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAcademicSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $academicSession = $this->route('academic_session');
        $academicSessionId = $academicSession instanceof AcademicSession ? $academicSession->id : null;

        return [
            'name' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_sessions', 'name')->ignore($academicSessionId)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array{name: string, starts_at: string, ends_at: string, is_active: bool}
     */
    public function payload(): array
    {
        return [
            'name' => (string) $this->validated('name'),
            'starts_at' => (string) $this->validated('starts_at'),
            'ends_at' => (string) $this->validated('ends_at'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
