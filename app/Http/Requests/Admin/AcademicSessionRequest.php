<?php

namespace App\Http\Requests\Admin;

use App\Enums\Term;
use App\Models\AcademicSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $session = $this->route('session');
        $sessionId = $session instanceof AcademicSession ? $session->id : null;

        return [
            'name' => [
                'required',
                'string',
                Rule::unique('academic_sessions')
                    ->where('term', $this->input('term'))
                    ->ignore($sessionId),
            ],
            'term' => ['required', Rule::enum(Term::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['boolean'],
        ];
    }
}
