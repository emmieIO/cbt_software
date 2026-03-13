<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bank:delete');
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:questions,id'],
        ];
    }
}
