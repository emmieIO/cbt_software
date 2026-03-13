<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class SchoolRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');
        $schoolId = $school instanceof \App\Models\School ? $school->id : $school;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:schools,name,'.$schoolId,
                function ($attribute, $value, $fail) use ($schoolId) {
                    $slug = Str::slug($value);
                    $exists = \App\Models\School::where('slug', $slug)
                        ->when($schoolId, fn ($query) => $query->where('id', '!=', $schoolId))
                        ->exists();

                    if ($exists) {
                        $fail('The name provided results in a duplicate branch identifier (slug).');
                    }
                },
            ],
            'type' => ['required', 'string', 'in:nursery,primary,secondary'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'array'],
            'contact_phone.*' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
